<?php
session_start();
require_once '../config/database.php';

if(isset($_SESSION['id_user']) && isset($_POST['lat'])) {
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $uid = $_SESSION['id_user'];

    // Update kolom latitude & longitude di tabel users
    $sql = "UPDATE users SET latitude = ?, longitude = ?, last_seen = NOW() WHERE id = ?";
    $pdo->prepare($sql)->execute([$lat, $lng, $uid]);
}