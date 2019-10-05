<?php
/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package SMSUtils
 */
interface SMSUtils_ISender {

    /**
     * Отправить SMS-сообщение
     *
     * @param string $sender Подпись отправителя
     * @param string $destination Телефон
     * @param string $text Сообщение
     */
    public function send($sender, $destination, $text);

}