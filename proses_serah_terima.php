<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';

auth_session_start();
$user_id_keluar = auth_require_login('index.php?page=login');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!csrf_validate_request()) {
        http_response_code(419);
        exit('CSRF token tidak valid');
    }

    $user_id_masuk  = (int)($_POST['user_id_masuk'] ?? 0);
    $catatan        = trim((string)($_POST['catatan'] ?? ''));

    if ($user_id_masuk <= 0 || $user_id_masuk === $user_id_keluar) {
        header("Location: index.php?page=serah_terima&status=invalid");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Simpan riwayat serah terima
        $sql_st = "INSERT INTO serah_terima (user_id_keluar, user_id_masuk, catatan_situasi) VALUES (?, ?, ?)";
        $pdo->prepare($sql_st)->execute([$user_id_keluar, $user_id_masuk, $catatan]);

        // 2. UPDATE PEMEGANG BARANG (Pindahkan dari ID Anda ke ID Petugas Baru)
        $upd_b = "UPDATE pinjam_barang SET user_id = ? WHERE user_id = ? AND status = 'Dipinjam'";
        $pdo->prepare($upd_b)->execute([$user_id_masuk, $user_id_keluar]);

        // 3. UPDATE PEMEGANG KENDARAAN (Pindahkan dari ID Anda ke ID Petugas Baru)
        $upd_k = "UPDATE pinjam_kendaraan SET user_id = ? WHERE user_id = ? AND status = 'Dipakai'";
        $pdo->prepare($upd_k)->execute([$user_id_masuk, $user_id_keluar]);

        $pdo->commit();
        header("Location: index.php?page=dashboard&status=serah_terima_success");
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: index.php?page=serah_terima&status=error");
        exit;
    }
}

header('Location: index.php?page=monitoring_serah_terima');
exit;