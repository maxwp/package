<?php

class SMSUtils_SenderSMSFly implements SMSUtils_ISender {

    public function __construct($apiLogin, $apiPassword) {
        $this->_apiURL = 'http://sms-fly.com/api/api.php';

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
        $provider = $destination;
        if ($sender) {
            $number = new XAsteriskSmsNumbers();
            $number->setService('SMSfly');
            $number->setSimid($sender);
            $number->setLimitCount(1);
            $num = $number->getNext();
            if ($num) {
                $provider = $num->getPhone();
            }
        }
        //$text = iconv('windows-1251', 'utf-8', htmlspecialchars($text));
        $start_time = 'AUTO'; // отправить немедленно или ставим дату и время  в формате YYYY-MM-DD HH:MM:SS
        $end_time = 'AUTO'; // автоматически рассчитать системой или ставим дату и время  в формате YYYY-MM-DD HH:MM:SS
        $rate = 1; // скорость отправки сообщений (1 = 1 смс минута).
        // Одиночные СМС сообщения отправляются всегда с максимальной скоростью.
        $lifetime = 4; // срок жизни сообщения 4 часа
        $myXML = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        $myXML .= "<request>";
        $myXML .= "<operation>SENDSMS</operation>";
        $myXML .= '<message start_time="'.$start_time.'" end_time="'.$end_time
            .'" lifetime="'.$lifetime.'" rate="'.$rate.'" desc="'.$provider.'" source="'.$sender.'">'."\n";
        $myXML .= "<body>".$text."</body>";
        $myXML .= "<recipient>".$destination."</recipient>";
        $myXML .=  "</message>";
        $myXML .= "</request>";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERPWD, $this->_apiLogin.':'.$this->_apiPassword);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $this->_apiURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml", "Accept: text/xml"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $myXML);
        $result = curl_exec($ch);
        curl_close($ch);
        $xml = simplexml_load_string($result);

        $result = (array) $xml;

        $string = (array) $result['to'];
        $string = $string['@attributes']['status'];
        if ($string == 'ACCEPTED') {
            $result = 'success';
        } else {
            $result = 'error';
        }
        return $result;
    }

    private $_apiURL;

    private $_apiLogin;

    private $_apiPassword;

}