<?php

class SMSUtils_SenderTurbosmsua implements SMSUtils_ISender {

    public function __construct($apiLogin, $apiPassword) {
        if (!class_exists('SoapClient')) {
            throw new SMSUtils_Exception('SOAPClient not found');
        }

        $this->_soapClient = new SoapClient('http://turbosms.in.ua/api/wsdl.html');

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
        if (!$this->_authStatus) {
            $auth = array (
                'login' => $this->_apiLogin,
                'password' => $this->_apiPassword
            );
            $result = $this->_soapClient->Auth($auth);
            $this->_authStatus = true;
        }

        if (strlen($destination) == 10 && strpos($destination, '0') === 0) {
            $destination = '38'.$destination;
        }

        $sms = array (
            'sender' => $sender,
            'destination' => '+'.$destination,
            'text' => $text
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

    private $_apiLogin;

    private $_apiPassword;

    private $_authStatus = false;

}