<?php
session_start();
require_once 'config/database.php';

$act = $_GET['act'] ?? $_POST['act'];

try {
    if ($act == 'tambah') {
        $nama   = $_POST['nama_shift'];
        $masuk  = $_POST['jam_masuk'];
        $pulang = $_POST['jam_pulang'];

        $stmt = $pdo->prepare("INSERT INTO shifts (nama_shift, jam_masuk, jam_pulang) VALUES (?, ?, ?)");
        $stmt->execute([$nama, $masuk, $pulang]);

        header("Location: index.php?page=master_shift");

    } elseif ($act == 'edit') {
        $id     = $_POST['id'];
        $nama   = $_POST['nama_shift'];
        $masuk  = $_POST['jam_masuk'];
        $pulang = $_POST['jam_pulang'];

        $stmt = $pdo->prepare("UPDATE shifts SET nama_shift=?, jam_masuk=?, jam_pulang=? WHERE id=?");
        $stmt->execute([$nama, $masuk, $pulang, $id]);

        header("Location: index.php?page=master_shift");

    } elseif ($act == 'hapus') {
        $id = $_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM shifts WHERE id=?");
        $stmt->execute([$id]);

        header("Location: index.php?page=master_shift");
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>