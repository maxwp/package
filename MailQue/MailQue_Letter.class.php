<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2015 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Класс-отправщик письма с вложениями.
 * Паттерн VO.
 *
 * @author atarget
 * @author Max
 *
 * @copyright WebProduction
 *
 * @package MailQue
 */
class MailQue_Letter {

    /**
     * Создать объект письма
     *
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $text
     */
    public function __construct($from, $to, $subject, $body, $cc = false) {
        $this->_to = trim($to);
        $this->_from = trim($from);
        $this->_subject = $subject;
        $this->_cc = $cc;
        $this->setBody($body);
    }

    /**
     * Получить адрес почты "куда" отправлять письмо
     *
     * @return string
     */
    public function getEmailTo() {
        return $this->_to;
    }

    /**
     * Получить адрес почты "копии"
     *
     * @return string
     */
    public function getCc() {
        return $this->_cc;
    }

    /**
     * Получить обратный адрес "от кого" отправлять письмо
     *
     * @return string
     */
    public function getEmailFrom() {
        return $this->_from;
    }

    /**
     * Получить тему письма
     *
     * @return string
     */
    public function getSubject() {
        return $this->_subject;
    }

    /**
     * Построить закодированную тему письма (в UTF-8 & base64 & prefix)
     *
     * @return string
     */
    public function makeSubjectEncoded() {
        // если тема письма не из цифр и букв A-Z - то это UTF-8
        if (preg_match("/^([a-z0-9-\:\.\,\s]*)$/ius", $this->_subject)) {
            return $this->_subject;
        } else {
            return "=?UTF-8?B?".base64_encode($this->_subject)."?=";
        }
    }

    /**
     * Задать содержимое письма
     *
     * @param string $text
     */
    public function setBody($text) {
        $this->_compiled = null;
        $this->_body = $text;
    }

    /**
     * Получить содержимое письма
     *
     * @return string
     */
    public function getBody() {
        return $this->_body;
    }

    /**
     * Вложить файл
     *
     * @param string $data Данные
     * @param string $name Имя файла
     * @param string $type MIME-тип
     */
    public function addAttachment($data, $name = 'Attachment', $type = "application/octet-stream") {
        $this->_attachments[] = array(
            'type' => $type,
            'data' => $data,
            'name' => $name
        );

        $this->_compiled = null;
    }

    /**
     * Получить вложение по его номеру (номер по счету)
     *
     * @param int $n
     *
     * @return string
     */
    public function getAttachment ($n) {
        return $this->_attachments[$n]['data'];
    }

    public function getAttachments() {
        return $this->_attachments;
    }

    public function deleteAttachment ($n) {
        $this->_compiled = null;
        unset($this->_attachments[$n]);
    }

    private function _makepart($part, $bound) {
        $result = "Content-Type: ".$part["type"];
        $result .= ($part["name"] ? "; name = \"".$part["name"]."\"" : "");
        $result .= "\n";
        $result .= "Content-Disposition: attachment; filename=\"$part[name]\"\n";
        $result .= "Content-Transfer-Encoding: base64\n\n";
        $result .= chunk_split(base64_encode($part['data']))."\n";

        return "\n--".$bound."\n".$result;
    }

    /**
     * Установить тип основного контента
     *
     * @param string $type
     */
    public function setBodyType($type) {
        $this->_body_type = $type;
    }

    /**
     * Получить тип основного контента
     *
     * @return string
     */
    public function getBodyType() {
        return $this->_body_type;
    }

    public function setEventid($eventid) {
        $this->_eventid = $eventid;
    }

    public function getEventid() {
        return $this->_eventid;
    }

    /**
     * Собрать письмо.
     * Параметр $full отвечает за "собирать полностью или нет".
     * Разница только в заголовках Subject и To.
     * Для функции mail() они не нужны, а в остальных случаях нужны.
     *
     * @param bool $full
     *
     * @return string
     */
    public function make($full = false) {
        if (!$full && !empty($this->_compiled)) {
            return $this->_compiled;
        }

        $boundary = "=+=--b".md5(uniqid(time()));
        $message = '';

        $from = $this->_from;
        if ($from) {
            $from = preg_replace_callback("/^\"(.+?)\"/ius", array($this, '_callbackEmail'), $from);
            $message .= "From: {$from}\n";
        }
        if (!empty($_SERVER['HTTP_HOST'])) {
            $message .= 'x-Mailer: '.$_SERVER['HTTP_HOST']."\n";
        }
        $message .= "MIME-Version: 1.0\n";
        $message .= "Date: ".date('r')."\n";
        if ($full) {
            $message .= "Subject: ".$this->makeSubjectEncoded()."\n";

            $to = $this->getEmailTo();
            $to = preg_replace_callback("/^\"(.+?)\"/ius", array($this, '_callbackEmail'), $to);
            $message .= "To: ".$to."\n";
            if ($this->_cc) {
                $message .= "Cc: ".$this->_cc."\n";
            }
        }
        $message .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\n\n--".$boundary."\n";
        $message .= "Content-Type: {$this->_body_type}; charset=utf-8\n";
        //$message .= "Content-Transfer-Encoding: 8bit\n";
        $message .= "Content-Transfer-Encoding: base64\n";
        $message .= "\n";
        //$message .= $this->getBody();
        $message .= chunk_split(base64_encode($this->getBody()));
        $message .= "\n";

        if ($this->_attachments) {
            foreach ($this->_attachments as $x) {
                $message .= $this->_makepart($x, $boundary);
            }
        }

        // сохраняем compiled-версию
        $this->_compiled = $message."--".$boundary."--\n";

        return $this->_compiled;
    }

    /**
     * Показать письмо
     *
     * @return string
     */
    public function __toString() {
        return $this->make();
    }

    private function _callbackEmail($x) {
        return "=?UTF-8?B?".base64_encode($x[1])."?=";
    }

    private $_body_type = 'text/plain';

    private $_to;

    private $_cc;

    private $_from;

    private $_fromName;

    private $_subject;

    private $_body;

    private $_eventid = 0;

    private $_compiled = false;

    private $_attachments;

}