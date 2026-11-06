<?php
session_start();
require_once '../config/database.php';

$uid = (int)($_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0);
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

if ($uid > 0 && is_numeric($lat) && is_numeric($lng)
    && abs((float)$lat) <= 90 && abs((float)$lng) <= 180) {
    $acc = is_numeric($_POST['acc'] ?? null) ? (float)$_POST['acc'] : null;

    $pdo->prepare("UPDATE users SET latitude = ?, longitude = ?, last_seen = NOW() WHERE id = ?")
        ->execute([$lat, $lng, $uid]);

    // Jejak rute; tabel dibuat dari database/patroli_track.sql
    $pdo->prepare("INSERT INTO patroli_track (user_id, latitude, longitude, accuracy_m) VALUES (?, ?, ?, ?)")
        ->execute([$uid, $lat, $lng, $acc]);
}
