<?php
require_once 'libraries/DahuaClient.php';
use Libraries\DahuaClient;
error_reporting(0);
ini_set('display_errors', 0);

try {
    $nvr = new DahuaClient('config/cctv_config.json');
    $channel = $_GET['ch'] ?? 1;
    $image = $nvr->getSnapshot($channel);
    header('Content-Type: image/jpeg');
    
    if ($image) {
        echo $image;
    } else {
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    }

} catch (Exception $e) {
    http_response_code(500);
}
?>