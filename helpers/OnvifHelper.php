<?php
class OnvifHelper {
    private $ip;
    private $user;
    private $pass;
    private $mediaUrl;

    public function __construct($ip, $user, $pass) {
        $this->ip = $ip;
        $this->user = $user;
        $this->pass = $pass;
        $this->mediaUrl = "http://{$this->ip}/onvif/device_service"; 
    }
    private function sendSoapRequest($xmlBody) {
        $envelope = '<?xml version="1.0" encoding="utf-8"?>
        <s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:tt="http://www.onvif.org/ver10/schema" xmlns:trt="http://www.onvif.org/ver10/media/wsdl">
            <s:Body>' . $xmlBody . '</s:Body>
        </s:Envelope>';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->mediaUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $envelope);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/soap+xml; charset=utf-8']);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->pass);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
    public function getProfileToken() {
        $xml = '<trt:GetProfiles/>';
        $response = $this->sendSoapRequest($xml);
        preg_match('/token="([^"]+)"/', $response, $matches);
        
        return $matches[1] ?? null;
    }
    public function getReplayUri($profileToken) {
        $xml = '
        <trt:GetReplayUri>
            <trt:StreamSetup>
                <tt:Stream>RTP-Unicast</tt:Stream>
                <tt:Transport>
                    <tt:Protocol>RTSP</tt:Protocol>
                </tt:Transport>
            </trt:StreamSetup>
            <trt:ProfileToken>'.$profileToken.'</trt:ProfileToken>
        </trt:GetReplayUri>';

        $response = $this->sendSoapRequest($xml);
        preg_match('/<tt:Uri>(.*?)<\/tt:Uri>/', $response, $matches);
        
        return $matches[1] ?? null;
    }
}
?>