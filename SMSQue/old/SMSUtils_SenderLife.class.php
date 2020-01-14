<?php

class SMSUtils_SenderLife implements SMSUtils_ISender {

    public function __construct($apiLogin, $apiPassword) {
        $this->_apiURL = 'https://api.life.com.ua/ip2sms/';

        $this->_apiLogin = $apiLogin;
        $this->_apiPassword = $apiPassword;
    }


    /**
     * Отправить SMS-сообщение
     *
     * @param string $sender
     * @param string $destination
     * @param string $text
     *
     * @return mixed
     */
    public function send($sender, $destination, $text) {
        $headers = array('Authorization: Basic ' . 
            base64_encode($this->_apiLogin . ":" . $this->_apiPassword), 'Content-Type: text/xml');
        
        $params = array('http' =>
            array(
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => '<?xml version="1.0" encoding="UTF-8" ?><message><service id="single" source="' . 
                    $sender . '"/><to>' . $destination . '</to><body content-type="text/plain">' . 
                    htmlspecialchars($text) . '</body></message>'
            ));

        $ctx = stream_context_create($params);
        $fp = fopen($this->_apiURL, 'rb', FALSE, $ctx);
        
        if ($fp) {
            $response = stream_get_contents($fp);
            $response = simplexml_load_string($response);
            if ($response->state == 'Accepted') {
                return 'success';
            }
        }
        
        return 'error';
    }

    private $_apiURL;

    private $_apiLogin;

    private $_apiPassword;

}