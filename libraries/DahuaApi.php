<?php
class DahuaAPI {
    private $ip;
    private $port;
    private $user;
    private $pass;
    private $protocol;

    public function __construct($configFile) {
        if (!file_exists($configFile)) {
            throw new Exception("Config file not found: " . $configFile);
        }
        $cfg = json_decode(file_get_contents($configFile), true);
        
        $this->ip = $cfg['ip'];
        $this->user = $cfg['user'];
        $this->pass = $cfg['pass'];
        $this->port = $cfg['port'];
        $this->protocol = ($this->port == 443 || ($cfg['protocol'] ?? '') == 'https') ? 'https' : 'http';
    }
    public function getSnapshot($channel = 1) {
        $url = "{$this->protocol}://{$this->ip}:{$this->port}/cgi-bin/snapshot.cgi?channel={$channel}";
        
        return $this->sendRequest($url);
    }
    public function getMJPEGStreamUrl($channel = 1) {
        return "{$this->protocol}://{$this->ip}:{$this->port}/cgi-bin/mjpg/video.cgi?channel={$channel}&subtype=1";
    }
    private function sendRequest($url) {
        if (!function_exists('curl_init')) {
            return false;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
            CURLOPT_USERPWD => "{$this->user}:{$this->pass}",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_USERAGENT => 'Dahua-PHP-Client/1.0'
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && !empty($result)) {
            return $result;
        }
        return false; // Gagal
    }
}
?>