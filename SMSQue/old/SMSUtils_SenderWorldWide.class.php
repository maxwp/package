<?php

class SMSUtils_SenderWorldWide implements SMSUtils_ISender {

    public function __construct($apiLogin, $apiPassword) {
        
        $this->_apiURL = 'http://httpapi.gms-worldwide.com/sms2bms.php';

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

        if (strlen($destination) == 10 && strpos($destination, '0') === 0) {
            $destination = '38'.$destination;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <SENDSMS>
           <VERSION>1.0</VERSION>
           <VP>120</VP>
           <SENDER>'.$sender.'</SENDER>
           <SEPERATOR>:</SEPERATOR>
           <TM_LIST>
                <TM>
                <DST_MSISDN_LIST>
                          <DST_MSISDN extraID="'.md5($destination.$text).'" param="text">'.$destination.'</DST_MSISDN>
                     </DST_MSISDN_LIST>
                     <CONTENT_LIST>
                          <CONTENT>
                               <CONTENT_TEXT>'.$text.'</CONTENT_TEXT>
                          </CONTENT>
                     </CONTENT_LIST>
                </TM>
           </TM_LIST>
        </SENDSMS>';

        $ch = curl_init($this->_apiURL);
        $headers = array(
            'Content-Type: text/xml',
            'Authorization: Basic '. base64_encode($this->_apiLogin.":".$this->_apiPassword)
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $this->_apiLogin . ":" . $this->_apiPassword);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $return = curl_exec($ch);
        curl_close($ch);


        if (preg_match('/200 OK/', $return)) {
            $result = 'success';
        } else {
            $result = 'error';
        }
        return $result;
    }

    private $_apiURL;

    private $_apiLogin;

    private $_apiPassword;

    private $_authStatus = false;

}