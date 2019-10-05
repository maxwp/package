<?php

class SMSUtils_SenderWebSMS implements SMSUtils_ISender {

    public function __construct($apiLogin, $apiPassword) {
        $this->_apiURL = 'http://www.websms.ru/http_in6.asp';

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

        $param = 'Http_username=' . urlencode($this->_apiLogin) . '&Http_password=' . urlencode($this->_apiPassword) .
            '&Phone_list=' . $destination . '&Message=' . urlencode($text);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_URL, $this->_apiURL);
        $u = trim(curl_exec($ch));
        curl_close($ch);

        preg_match("/message_id\s*=\s*[0-9]+/i", $u, $arr_id);
        $id = preg_replace("/message_id\s*=\s*/i", "", @strval($arr_id[0]));

        if ($id) {
            $result = 'success';
        } else {
            $result = 'error';
        }
        return $result;
    }

    private $_soapClient;

    private $_apiURL;

    private $_apiLogin;

    private $_apiPassword;

}