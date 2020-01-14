<?php
/**
 * @copyright WebProduction
 * @package SMSUtils
 */
class SMSUtils_SenderSMSCkz implements SMSUtils_ISender {

    public function __construct($apiLogin, $apiPassword) {
        if (!class_exists('SoapClient')) {
            throw new SMSUtils_Exception('SOAPClient not found');
        }

        $this->_soapClient = new SoapClient('http://smsc.kz/sys/soap.php?wsdl');

        $this->_apiLogin = $apiLogin;
        $this->_apiPassword = $apiPassword;
    }


    /**
     * Отправить SMS-сообщение
     * @param string $sender
     * @param string $destination
     * @param string $text
     * @return mixed
     */
    public function send($sender, $destination, $text) {
        $answer = $this->_soapClient->send_sms(array(
        'login' => $this->_apiLogin,
        'psw' => $this->_apiPassword,
        'phones' => $destination,
        'mes' => $text,
        'id' => '',
        'sender' => $sender,
        'time' => 0
        ));


        $logData = array();
        $logData['login'] = $this->_apiLogin;
        $logData['sender'] = $sender;
        $logData['destination'] = $destination;
        $logData['result'] = $answer->sendresult->error;

        if (isset($answer->sendresult->error)) {
            $result = 'error';
        } else {
            $result = 'success';
        }

        return $result;
    }

    private $_soapClient;

    private $_apiLogin;

    private $_apiPassword;

}