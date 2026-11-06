<?php
// Query & render baris laporan presensi (dipakai halaman utama dan AJAX realtime).

function presensi_report_fetch(PDO $pdo, string $mulai, string $selesai, string $search): array
{
    // menit_telat = selisih jam masuk aktual vs jam shift (0 jika tepat waktu / tanpa shift)
    $sql = "SELECT p.*, u.nama_lengkap, s.nama_shift, s.jam_masuk AS shift_masuk, s.jam_pulang AS shift_pulang,
                   CASE WHEN p.jam_masuk IS NOT NULL AND s.jam_masuk IS NOT NULL
                             AND TIME_TO_SEC(p.jam_masuk) > TIME_TO_SEC(s.jam_masuk)
                        THEN FLOOR((TIME_TO_SEC(p.jam_masuk) - TIME_TO_SEC(s.jam_masuk)) / 60)
                        ELSE 0 END AS menit_telat
            FROM presensi p
            JOIN users u ON p.id_user = u.id
            LEFT JOIN shifts s ON u.shift_id = s.id
            WHERE p.tgl_presensi BETWEEN :mulai AND :selesai";
    $params = ['mulai' => $mulai, 'selesai' => $selesai];
    if ($search !== '') {
        $sql .= " AND u.nama_lengkap LIKE :search_name";
        $params['search_name'] = "%$search%";
    }
    $sql .= " ORDER BY p.tgl_presensi ASC, p.jam_masuk ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function presensi_report_stats(array $data): array
{
    $s = ['h' => 0, 'l' => 0, 'i' => 0, 'a' => 0];
    foreach ($data as $r) {
        if ($r['status'] == 'Hadir' || $r['status'] == 'Terlambat') $s['h']++;
        if (!empty($r['jam_lembur_mulai'])) $s['l']++;
        if (in_array($r['status'], ['Izin', 'Sakit', 'Cuti'])) $s['i']++;
        if ($r['status'] == 'Alpha') $s['a']++;
    }
    return $s;
}

function presensi_report_rows(array $data, string $imgBase = 'assets/img/presensi/'): string
{
    if (empty($data)) {
        return '<tr><td colspan="11" class="text-center py-5 text-muted">Data tidak ditemukan.</td></tr>';
    }
    $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    $html = '';
    $no = 1;
    foreach ($data as $row) {
        $telat = (int)$row['menit_telat'];
        $jamMasuk = $row['jam_masuk'] ? substr($row['jam_masuk'], 0, 5) : '-';
        $masukHtml = $telat > 0
            ? '<span class="text-late">' . $e($jamMasuk) . '</span><div class="text-late small">Telat ' . $telat . ' menit</div>'
            : '<span class="badge bg-light text-dark border">' . $e($jamMasuk) . '</span>';

        $shiftHtml = $row['nama_shift']
            ? '<div class="fw-bold">' . $e($row['nama_shift']) . '</div><small class="text-muted">'
              . $e(substr($row['shift_masuk'], 0, 5)) . ' - ' . $e(substr((string)$row['shift_pulang'], 0, 5)) . '</small>'
            : '<span class="text-muted">-</span>';

        $lat = $row['lat_absen'] ?? null;
        $lng = $row['lng_absen'] ?? null;
        if ($lat !== null && $lat !== '' && $lng !== null && $lng !== '') {
            $lokHtml = '<a href="https://www.google.com/maps?q=' . urlencode($lat . ',' . $lng) . '" target="_blank" rel="noopener" class="text-decoration-none">'
                     . '<i class="fas fa-map-marker-alt text-danger me-1"></i>' . $e(round((float)$lat, 6)) . ', ' . $e(round((float)$lng, 6)) . '</a>';
        } else {
            $lokHtml = $e($row['lokasi_masuk'] ?: '-');
        }

        $fotoFile = $row['foto_masuk'] ?: ($row['foto_wajah'] ?? '');
        $fotoHtml = $fotoFile
            ? '<a href="' . $e($imgBase . $fotoFile) . '" target="_blank"><img src="' . $e($imgBase . $fotoFile) . '" alt="Foto" class="foto-presensi"></a>'
            : '<span class="text-muted">-</span>';

        $bg = ($row['status'] == 'Hadir' ? 'success' : 'warning');
        $lembur = $row['jam_lembur_mulai']
            ? $e(substr($row['jam_lembur_mulai'], 0, 5) . ' - ' . substr((string)$row['jam_lembur_selesai'], 0, 5))
            : '-';

        $html .= '<tr class="text-center">'
            . '<td>' . $no++ . '</td>'
            . '<td>' . date('d/m/Y', strtotime($row['tgl_presensi'])) . '</td>'
            . '<td class="text-start"><div class="fw-bold text-dark">' . $e($row['nama_lengkap']) . '</div><small class="text-muted">ID: PEG-' . $e($row['id_user']) . '</small></td>'
            . '<td>' . $shiftHtml . '</td>'
            . '<td>' . $masukHtml . '</td>'
            . '<td><span class="badge bg-light text-dark border">' . ($row['jam_pulang'] ? $e(substr($row['jam_pulang'], 0, 5)) : '-') . '</span></td>'
            . '<td class="text-primary fw-bold">' . $lembur . '</td>'
            . '<td class="text-start small">' . $lokHtml . '</td>'
            . '<td>' . $fotoHtml . '</td>'
            . '<td><span class="badge bg-' . $bg . ' rounded-pill px-3">' . $e(strtoupper($row['status'])) . '</span></td>'
            . '<td class="text-muted small">' . $e($row['keterangan'] ?: '-') . '</td>'
            . '</tr>';
    }
    return $html;
}
