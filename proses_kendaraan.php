<?php
require_once 'config/database.php';
require_once 'config/helper_notif.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';
require_once 'config/audit.php';

auth_session_start();
$currentUserId = auth_require_login('index.php?page=login');

$act = $_POST['act'] ?? $_GET['act'] ?? '';

$masterActs = ['tambah_kendaraan', 'edit_kendaraan', 'hapus_kendaraan'];
$operationalActs = ['pakai', 'kembali', 'lapor_masalah'];

if (in_array($act, $masterActs, true)) {
    auth_require_roles(['admin', 'administrator']);
}

if (in_array($act, array_merge($masterActs, $operationalActs), true) && !csrf_validate_request()) {
    http_response_code(419);
    exit('CSRF token tidak valid');
}

if (!function_exists('table_has_column')) {
    function table_has_column(PDO $pdo, $table, $column) {
        static $cache = [];
        $table = (string)$table;
        $column = (string)$column;

        if (!isset($cache[$table])) {
            $cache[$table] = [];
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM `" . str_replace('`', '', $table) . "`");
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $name = $row['Field'] ?? '';
                    if ($name !== '') {
                        $cache[$table][strtolower($name)] = true;
                    }
                }
            } catch (Exception $e) {
                return false;
            }
        }

        return isset($cache[$table][strtolower($column)]);
    }
}

try {
    // --- 1. TAMBAH KENDARAAN ---
    if ($act == 'tambah_kendaraan') {
        $nama  = trim((string)($_POST['nama_kendaraan'] ?? ''));
        $nopol = strtoupper(trim((string)($_POST['nopol'] ?? '')));
        $jenis = trim((string)($_POST['jenis'] ?? ''));

        if ($nama === '' || $nopol === '' || !in_array($jenis, ['Mobil', 'Motor'], true)) {
            header("Location: index.php?page=master_kendaraan&status=invalid");
            exit;
        }

        $sql = "INSERT INTO kendaraan (nama_kendaraan, nopol, jenis, status) VALUES (?, ?, ?, 'Tersedia')";
        if ($pdo->prepare($sql)->execute([$nama, $nopol, $jenis])) {
            header("Location: index.php?page=master_kendaraan&status=success");
            exit;
        }
    }

    // --- 2. UPDATE STATUS KENDARAAN ---
    elseif ($act == 'edit_kendaraan') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? ''));
        $nama   = trim((string)($_POST['nama_kendaraan'] ?? ''));

        if ($id <= 0 || !in_array($status, ['Tersedia', 'Digunakan', 'Servis', 'Rusak'], true)) {
            header("Location: index.php?page=master_kendaraan&status=invalid");
            exit;
        }

        $sql = "UPDATE kendaraan SET status = ? WHERE id = ?";
        if ($pdo->prepare($sql)->execute([$status, $id])) {
            
            // [NOTIFIKASI] Jika kendaraan tidak bisa digunakan
            if ($status == 'Servis' || $status == 'Rusak') {
                $penerima = getAdminIds($pdo); // Kirim ke Admin & Pengawas
                sendNotification(
                    $pdo, 
                    $penerima, 
                    'alert', 
                    'Kendaraan Tidak Siap Pakai! ??', 
                    "Unit $nama sedang dalam kondisi $status. Jangan gunakan untuk patroli sementara waktu.", 
                    'index.php?page=master_kendaraan'
                );
            }

            header("Location: index.php?page=master_kendaraan&status=updated");
            exit;
        }
    }

    // --- 3. HAPUS KENDARAAN ---
    elseif ($act == 'hapus_kendaraan') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header("Location: index.php?page=master_kendaraan&status=invalid");
            exit;
        }

        $pdo->prepare("DELETE FROM kendaraan WHERE id = ?")->execute([$id]);
        header("Location: index.php?page=master_kendaraan&status=deleted");
        exit;
    }

    // --- 4. KENDARAAN PAKAI (OPERASIONAL) ---
    elseif ($act == 'pakai') {
        $kendaraanId = (int)($_POST['kendaraan_id'] ?? 0);
        $kmAwal = (int)($_POST['km_awal'] ?? 0);
        $keperluan = trim((string)($_POST['keperluan'] ?? ''));

        if ($kendaraanId <= 0 || $kmAwal < 0 || $keperluan === '') {
            header("Location: index.php?page=kendaraan_pakai&status=error&msg=" . urlencode('Data penggunaan kendaraan tidak valid'));
            exit;
        }

        $pdo->beginTransaction();

        $stmtK = $pdo->prepare("SELECT id, merk_model, no_polisi, status FROM kendaraan WHERE id = ? FOR UPDATE");
        $stmtK->execute([$kendaraanId]);
        $kendaraan = $stmtK->fetch(PDO::FETCH_ASSOC);

        if (!$kendaraan || !in_array((string)($kendaraan['status'] ?? ''), ['Tersedia', 'Siap'], true)) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            header("Location: index.php?page=kendaraan_pakai&status=error&msg=" . urlencode('Kendaraan tidak tersedia'));
            exit;
        }

        $insertCols = ['kendaraan_id', 'user_id', 'km_awal', 'keperluan', 'status'];
        $insertPlaceholders = ['?', '?', '?', '?', '?'];
        $insertVals = [$kendaraanId, $currentUserId, $kmAwal, $keperluan, 'Dipakai'];

        if (table_has_column($pdo, 'pinjam_kendaraan', 'tgl_ambil')) {
            $insertCols[] = 'tgl_ambil';
            $insertPlaceholders[] = 'NOW()';
        } elseif (table_has_column($pdo, 'pinjam_kendaraan', 'tgl_pinjam')) {
            $insertCols[] = 'tgl_pinjam';
            $insertPlaceholders[] = 'NOW()';
        }

        $sqlInsert = "INSERT INTO pinjam_kendaraan (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $insertPlaceholders) . ")";
        $pdo->prepare($sqlInsert)->execute($insertVals);
        $pinjamId = (int)$pdo->lastInsertId();

        $pdo->prepare("UPDATE kendaraan SET status = 'Dipakai' WHERE id = ?")->execute([$kendaraanId]);

        audit_log_event('kendaraan.pakai', [
            'kendaraan_id' => $kendaraanId,
            'pinjam_id' => $pinjamId,
            'km_awal' => $kmAwal,
            'keperluan' => $keperluan
        ]);

        $pdo->commit();
        header("Location: index.php?page=kendaraan_pakai&status=success");
        exit;
    }

    // --- 5. KENDARAAN KEMBALI (OPERASIONAL) ---
    elseif ($act == 'kembali') {
        $idPinjam = (int)($_POST['id_pinjam'] ?? 0);
        $kendaraanId = (int)($_POST['kendaraan_id'] ?? 0);
        $kmAkhir = (int)($_POST['km_akhir'] ?? -1);
        $kondisiAkhir = trim((string)($_POST['kondisi_akhir'] ?? ''));

        if ($idPinjam <= 0 || $kendaraanId <= 0 || $kmAkhir < 0 || !in_array($kondisiAkhir, ['Baik', 'Perlu Service', 'Rusak'], true)) {
            header("Location: index.php?page=kendaraan_kembali&status=error&msg=" . urlencode('Data pengembalian kendaraan tidak valid'));
            exit;
        }

        $pdo->beginTransaction();

        $stmtP = $pdo->prepare("SELECT id, km_awal, status FROM pinjam_kendaraan WHERE id = ? AND kendaraan_id = ? FOR UPDATE");
        $stmtP->execute([$idPinjam, $kendaraanId]);
        $pinjam = $stmtP->fetch(PDO::FETCH_ASSOC);

        if (!$pinjam || (string)($pinjam['status'] ?? '') !== 'Dipakai') {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            header("Location: index.php?page=kendaraan_kembali&status=error&msg=" . urlencode('Data pemakaian tidak ditemukan'));
            exit;
        }

        $kmAwalRow = (int)($pinjam['km_awal'] ?? 0);
        if ($kmAkhir < $kmAwalRow) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            header("Location: index.php?page=kendaraan_kembali&status=error&msg=" . urlencode('KM akhir tidak boleh lebih kecil dari KM awal'));
            exit;
        }

        $updates = ["status = ?"];
        $updateVals = ['Dikembalikan'];

        if (table_has_column($pdo, 'pinjam_kendaraan', 'km_akhir')) {
            $updates[] = "km_akhir = ?";
            $updateVals[] = $kmAkhir;
        }

        if (table_has_column($pdo, 'pinjam_kendaraan', 'kondisi_akhir')) {
            $updates[] = "kondisi_akhir = ?";
            $updateVals[] = $kondisiAkhir;
        }

        if (table_has_column($pdo, 'pinjam_kendaraan', 'tgl_kembali')) {
            $updates[] = "tgl_kembali = NOW()";
        }

        $updateVals[] = $idPinjam;
        $sqlUpdatePinjam = "UPDATE pinjam_kendaraan SET " . implode(', ', $updates) . " WHERE id = ?";
        $pdo->prepare($sqlUpdatePinjam)->execute($updateVals);

        $statusKendaraan = 'Tersedia';
        if ($kondisiAkhir === 'Perlu Service') {
            $statusKendaraan = 'Service';
        } elseif ($kondisiAkhir === 'Rusak') {
            $statusKendaraan = 'Rusak';
        }

        $pdo->prepare("UPDATE kendaraan SET status = ? WHERE id = ?")->execute([$statusKendaraan, $kendaraanId]);

        audit_log_event('kendaraan.kembali', [
            'kendaraan_id' => $kendaraanId,
            'pinjam_id' => $idPinjam,
            'km_akhir' => $kmAkhir,
            'kondisi_akhir' => $kondisiAkhir,
            'status_kendaraan' => $statusKendaraan
        ]);

        $pdo->commit();
        header("Location: index.php?page=kendaraan_kembali&status=success");
        exit;
    }

    // --- 6. LAPOR MASALAH KENDARAAN (OPERASIONAL) ---
    elseif ($act == 'lapor_masalah') {
        $kendaraanId = (int)($_POST['kendaraan_id'] ?? 0);
        $jenisMasalah = trim((string)($_POST['jenis_masalah'] ?? ''));
        $detail = trim((string)($_POST['detail_kejadian'] ?? ''));

        if ($kendaraanId <= 0 || $detail === '' || !in_array($jenisMasalah, ['Rusak', 'Hilang', 'Service'], true)) {
            header("Location: index.php?page=kendaraan_lapor&status=error&msg=" . urlencode('Data laporan masalah tidak valid'));
            exit;
        }

        $pdo->beginTransaction();

        $stmtK = $pdo->prepare("SELECT id, merk_model FROM kendaraan WHERE id = ? FOR UPDATE");
        $stmtK->execute([$kendaraanId]);
        $kendaraan = $stmtK->fetch(PDO::FETCH_ASSOC);

        if (!$kendaraan) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            header("Location: index.php?page=kendaraan_lapor&status=error&msg=" . urlencode('Kendaraan tidak ditemukan'));
            exit;
        }

        if (table_has_column($pdo, 'kendaraan', 'keterangan')) {
            $sql = "UPDATE kendaraan SET status = ?, keterangan = ? WHERE id = ?";
            $ket = '[LAPORAN] ' . $detail;
            $pdo->prepare($sql)->execute([$jenisMasalah, $ket, $kendaraanId]);
        } else {
            $sql = "UPDATE kendaraan SET status = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$jenisMasalah, $kendaraanId]);
        }

        $penerima = getAdminIds($pdo);
        if (!empty($penerima)) {
            $namaKendaraan = (string)($kendaraan['merk_model'] ?? ('ID ' . $kendaraanId));
            sendNotification(
                $pdo,
                $penerima,
                'alert',
                'Laporan Masalah Kendaraan',
                "Unit {$namaKendaraan} dilaporkan {$jenisMasalah}.",
                'index.php?page=kendaraan_lapor'
            );
        }

        audit_log_event('kendaraan.lapor_masalah', [
            'kendaraan_id' => $kendaraanId,
            'jenis_masalah' => $jenisMasalah,
            'detail' => $detail
        ]);

        $pdo->commit();
        header("Location: index.php?page=kendaraan_lapor&status=success");
        exit;
    }

} catch (Exception $e) {
    if (in_array($act, ['pakai'], true)) {
        header("Location: index.php?page=kendaraan_pakai&status=error");
        exit;
    }

    if (in_array($act, ['kembali'], true)) {
        header("Location: index.php?page=kendaraan_kembali&status=error");
        exit;
    }

    if (in_array($act, ['lapor_masalah'], true)) {
        header("Location: index.php?page=kendaraan_lapor&status=error");
        exit;
    }

    header("Location: index.php?page=master_kendaraan&status=error");
    exit;
}

if (in_array($act, ['pakai'], true)) {
    header("Location: index.php?page=kendaraan_pakai");
    exit;
}

if (in_array($act, ['kembali'], true)) {
    header("Location: index.php?page=kendaraan_kembali");
    exit;
}

if (in_array($act, ['lapor_masalah'], true)) {
    header("Location: index.php?page=kendaraan_lapor");
    exit;
}

header("Location: index.php?page=master_kendaraan");
exit;
?>