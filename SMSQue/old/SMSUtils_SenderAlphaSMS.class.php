<?php

class SMSUtils_SenderAlphaSMS implements SMSUtils_ISender {

    public function __construct($apiLogin, $apiPassword, $apiId) {
        $this->_apiId = $apiId;

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
        $sender;

        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<package login="'.$this->_apiLogin.'" password="'.$this->_apiPassword.'">';
        $xml .= '<message>';
        $xml .= '<msg recipient="'.$destination.'" sender="' .$sender.'" type="0">'.$text.'</msg>';
        $xml .= '</message>';
        $xml .= '</package>';

        $url = 'https://alphasms.ua/api/xml.php';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $xml = @simplexml_load_string($response);

        // заходим на необходимые поиции внутри xml
        $responseArray = (array) $xml->xpath('message/msg');
        $responseArray = (array) $responseArray[0];
        $responseArray['@attributes']['sms_count'];


        if ($responseArray['@attributes']['sms_count']) {
            $result = 'success';
        } else {
            $result = 'error';
        }
        return $result;
    }

    private $_apiId;

    private $_apiLogin;

    private $_apiPassword;
}