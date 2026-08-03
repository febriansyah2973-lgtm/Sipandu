<?php
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    die("Error: ID Lokasi tidak ditemukan.");
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM checkpoints WHERE id = ?");
$stmt->execute([$id]);
$lokasi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lokasi) {
    die("Error: Data lokasi tidak ditemukan.");
}
$baseUrl = "http://localhost/sistem_patroli"; 
$qrData  = $baseUrl . "/scan.php?code=" . $lokasi['kode_qr'];
$qrImage = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrData);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR - <?= htmlspecialchars($lokasi['nama_lokasi']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .card-qr {
            width: 350px;
            background: white;
            border: 2px solid #000;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
        }

        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h2 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 12px; color: #555; }

        .qr-box img {
            width: 200px;
            height: 200px;
            border: 1px solid #ddd;
            padding: 5px;
        }

        .info { margin-top: 15px; }
        .info h3 { margin: 5px 0; font-size: 18px; color: #000; }
        .info .code { 
            font-family: 'Courier New', monospace; 
            background: #eee; 
            padding: 2px 8px; 
            border-radius: 4px;
            font-size: 14px;
        }

        .footer {
            margin-top: 20px;
            font-size: 10px;
            color: #888;
        }
        .btn-print {
            margin-top: 20px;
            padding: 10px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-back {
            background: #64748b;
            text-decoration: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            margin-left: 10px;
        }
        @media print {
            body { 
                background: white; 
                display: block;
            }
            .no-print { display: none !important; }
            .card-qr {
                box-shadow: none;
                border: 2px solid #000;
                width: 100%;
                margin: 0 auto;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <div class="container-center">
        <div class="card-qr">
            <div class="header">
                <h2>CHECKPOINT</h2>
                <p>SISTEM PATROLI KEAMANAN</p>
            </div>

            <div class="qr-box">
                <img src="<?= $qrImage ?>" alt="QR Code">
            </div>

            <div class="info">
                <h3><?= htmlspecialchars($lokasi['nama_lokasi']) ?></h3>
                <span class="code"><?= htmlspecialchars($lokasi['kode_qr']) ?></span>
                <br>
                <small><?= htmlspecialchars($lokasi['keterangan']) ?></small>
            </div>

            <div class="footer">
                Scan QR ini untuk melakukan absensi/patroli.
            </div>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" class="btn-print">🖨️ Cetak / Print</button>
            <a href="index.php?page=master_lokasi" class="btn-back">Kembali</a>
        </div>
    </div>

</body>
</html>