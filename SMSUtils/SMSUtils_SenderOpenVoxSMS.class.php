<?php

class SMSUtils_SenderOpenVoxSMS implements SMSUtils_ISender {

    public function __construct($apiLogin, $apiPassword, $apiurl, $provider) {

        $this->_apiURL = $apiurl;
        if (!$provider) {
            $provider = 1;
        }
        $this->_apiProvider = $provider;

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
        $provider = $this->_apiProvider;
        if ($sender) {
            $simNumber = new XAsteriskSmsNumbers();
            $simNumber->setService('SMSOpenVox');
            $simNumber->setManagerid($sender);
            $simNumber->setLimitCount(1);
            $sim = $simNumber->getNext();
            if ($sim) {
                $provider = $sim->getSimid();
            } else {
                $simNumber = new XAsteriskSmsNumbers();
                $simNumber->setLimitCount(1);
                $sim = $simNumber->getNext();
                if ($sim) {
                    $provider = $sim->getSimid();
                }
            }
        }
        if (strlen($destination) == 10 && strpos($destination, '0') === 0) {
            $destination = '38'.$destination;
        }
        $url = $this->_apiURL.'sendsms?';
        $param = 'username='.urlencode($this->_apiLogin).'&password='.urlencode($this->_apiPassword).
            '&phonenumber='.$destination.'&message='.urlencode($text).'&port='.$provider.'&report=String&timeout=5';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_URL, $url);
        $insert = trim(curl_exec($ch));
        curl_close($ch);


        $content = '';
        if (preg_match("/result\:\s*(.+?)\n/ius", $insert, $r)) {
            $content = trim($r[1]);
        }
        if ($content == 'success') {
            $result = 'success';
        } else {
            $result = 'error';
        }
        return $result;
    }

    private $_apiURL;

    private $_apiLogin;

    private $_apiPassword;

    private $_apiProvider;

    private $_authStatus = false;

}