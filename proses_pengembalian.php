<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';
require_once 'config/audit.php';

auth_session_start();
auth_require_login();

if (!csrf_validate_request()) {
    http_response_code(419);
    exit('CSRF token tidak valid');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['act'] ?? '') == 'kembali') {
    $id_pinjam = (int)($_POST['id_pinjam'] ?? 0);
    $barang_id = (int)($_POST['barang_id'] ?? 0);
    $jumlah    = (int) $_POST['jumlah'];

    if ($id_pinjam <= 0 || $barang_id <= 0 || $jumlah <= 0) {
        header("Location: index.php?page=barang_kembali&status=error&msg=" . urlencode('Data pengembalian tidak valid.'));
        exit();
    }

    try {
        $pdo->beginTransaction();
        $sqlReturn = "UPDATE pinjam_barang SET status = 'Dikembalikan', tgl_kembali = NOW() WHERE id = ?";
        $pdo->prepare($sqlReturn)->execute([$id_pinjam]);
        $sqlStock = "UPDATE barang SET jml_baik = jml_baik + ? WHERE id = ?";
        $pdo->prepare($sqlStock)->execute([$jumlah, $barang_id]);
        audit_log_event('barang.kembali', [
            'pinjam_id' => $id_pinjam,
            'barang_id' => $barang_id,
            'jumlah' => $jumlah
        ]);

        $pdo->commit();
        header("Location: index.php?page=barang_kembali&status=success");
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: index.php?page=barang_kembali&status=error&msg=" . urlencode($e->getMessage()));
    }
    exit();
}