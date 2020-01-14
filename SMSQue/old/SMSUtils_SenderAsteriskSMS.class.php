<?php

class SMSUtils_SenderAsteriskSMS implements SMSUtils_ISender {

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
            $serviceArray = array('SMSAsterisk', 'SMSUtils_SenderAsteriskSMS');
            $simNumber = new XAsteriskSmsNumbers();
            $simNumber->filterService($serviceArray);
            $simNumber->setManagerid($sender);
            $simNumber->setLimitCount(1);
            $sim = $simNumber->getNext();
            if ($sim) {
                $provider = $sim->getSimid();
            }
        }
        if (strlen($destination) == 10 && strpos($destination, '0') === 0) {
            $destination = '38'.$destination;
        }
        $url = $this->_apiURL.'dosend.php';
        $param = 'USERNAME=' . urlencode($this->_apiLogin) . '&PASSWORD=' . urlencode($this->_apiPassword) .
            '&smsprovider=' . $provider .'&smsnum=' . $destination . '&method=2&Memo=' . urlencode($text);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_URL, $url);
        $insert = trim(curl_exec($ch));
        curl_close($ch);


        $content = '';
        if (preg_match("/location.+?\?(.+?)\'/ius", $insert, $r)) {
            $url2 = $this->_apiURL.'resend.php';
            $content = file_get_contents($url2."?".$r[1]);
        }
        if (preg_match('/Resending Messge/ius', $content)) {
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