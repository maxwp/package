<?php

class SMSUtils_SenderSMSru implements SMSUtils_ISender {

    public function __construct($apiLogin, $apiPassword, $apiurl, $apiId) {

        $this->_apiURL = $apiurl;
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
        $sender = $sender;
        $url = $this->_apiURL;
        $apiId = $this->_apiId;
        $param = array(
            "api_id" => $apiId,
            "to" => $destination,
            "text" => $text
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_URL, $url);
        $insert = trim(curl_exec($ch));
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpStatus == 200) {
            $result = 'success';
        } else {
            $result = 'error';
        }
        return $result;
    }

    private $_apiURL;

    private $_apiId;

    private $_apiLogin;

    private $_apiPassword;

    private $_authStatus = false;

}