<?php
namespace Libraries;

use Exception;

class DahuaClient {
    private $config;
    private $baseUrl;

    public function __construct($configFile) {
        if (!file_exists($configFile)) {
            $this->logError("Config file missing: $configFile");
            throw new Exception("Konfigurasi CCTV tidak ditemukan.");
        }
        
        $this->config = json_decode(file_get_contents($configFile), true);
        $this->setupBaseUrl();
    }

    private function setupBaseUrl() {
        $c = $this->config;
        $protocol = ($c['port'] == 443 || ($c['protocol'] ?? '') == 'https') ? 'https' : 'http';
        $this->baseUrl = "{$protocol}://{$c['ip']}:{$c['port']}";
    }
    public function getSnapshot($channel = 1) {
        $endpoint = "/cgi-bin/snapshot.cgi?channel={$channel}";
        return $this->sendRequest($endpoint);
    }
    private function sendRequest($endpoint) {
        $url = $this->baseUrl . $endpoint;
        $user = $this->config['user'];
        $pass = $this->config['pass'];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH       => CURLAUTH_ANY,
            CURLOPT_USERPWD        => "$user:$pass",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_USERAGENT      => 'Sipandu-NVR-Client/2.0'
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($httpCode == 200 && !empty($result)) {
            return $result;
        }

        $this->logError("Gagal akses $url. Code: $httpCode. Error: $error");
        return false;
    }
    private function logError($msg) {
        $logFile = __DIR__ . '/../config/cctv_error.log';
        $date = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$date] $msg" . PHP_EOL, FILE_APPEND);
    }
}
?>