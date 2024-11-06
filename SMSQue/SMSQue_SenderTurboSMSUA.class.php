<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

class SMSQue_SenderTurboSMSUA implements SMSQue_ISender {

    public function __construct($sender, $apiLogin, $apiPassword) {
        if (!class_exists('SoapClient')) {
            throw new SMSUtils_Exception('SOAPClient not found');
        }

        $this->_soapClient = new SoapClient('http://turbosms.in.ua/api/wsdl.html');

        $this->_sender = $sender;
        $this->_apiLogin = $apiLogin;
        $this->_apiPassword = $apiPassword;
    }

    public function send($from, $to, $body) {
        if (!$this->_authStatus) {
            $auth = array (
                'login' => $this->_apiLogin,
                'password' => $this->_apiPassword
            );
            $result = $this->_soapClient->Auth($auth);
            $this->_authStatus = true;
        }

        if (strlen($to) == 10 && strpos($to, '0') === 0) {
            $to = '38'.$to;
        }

        if (!$from) {
            $from = $this->_sender;
        }

        $sms = array (
            'sender' => $from,
            'destination' => '+'.$to,
            'text' => $body,
        );

        $answer = $this->_soapClient->SendSMS($sms);

        $answer = $answer->SendSMSResult->ResultArray;

        // в ответ может прийти как строка так и массив
        if (is_array($answer)) {
            $result = $answer[0];
        } else {
            $result = $answer;
        }
        if ($result == "Сообщения успешно отправлены") {
            $result = 'success';
        } else {
            $result = $answer;
        }

        return $result;
    }

    private $_soapClient;

    private $_sender;

    private $_apiLogin;

    private $_apiPassword;

    private $_authStatus = false;

}