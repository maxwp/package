<?php
/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package SMSUtils
 */
class SMSUtils {

    public function __construct(SMSUtils_ISender $sender) {
        $this->_sender = $sender;
    }

    /**
     * Отправить SMS-сообщение
     *
     * @param string $sender Подпись отправителя
     * @param string $destinationPhone Телефон (кому)
     * @param string $text Сообщение
     */
    public function send($sender, $destinationPhone, $text, $startDate = false) {
        if (!$sender) {
        	throw new SMSUtils_Exception('sender');
        }
        if (!$text) {
        	throw new SMSUtils_Exception('text');
        }

        $destinationPhone = preg_replace("/\D/", '', $destinationPhone);
        if (!$destinationPhone) {
        	throw new SMSUtils_Exception('phone');
        }

        $text = trim($text);

        $this->getSender()->send($sender, $destinationPhone, $text, $startDate);
    }

    /**
     * Получить отправщик SMS
     *
     * @return SMSUtils_ISender
     */
    public function getSender() {
        return $this->_sender;
    }

    private $_sender;

}