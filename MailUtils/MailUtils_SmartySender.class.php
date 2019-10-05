<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2015 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Отправщик писем по шаблонам Smarty
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package MailUtils
 */
class MailUtils_SmartySender {

    /**
     * Создать SmartySender на основе готового кода шаблона
     *
     * @return MailUtils_SmartySender
     */
    public static function CreateFromTemplateData($data) {
        $data = trim($data);
        if (!$data) {
            throw new MailUtils_Exception('Empty data');
        }

        // сохраняем во временный файл
        $file = __DIR__.'/compile/'.md5($data).'.html';
        file_put_contents($file, $data, LOCK_EX);

        return new self($file);
    }

    /**
     * Создать SmartySender на основе шаблон-файла
     *
     * @return MailUtils_SmartySender
     */
    public static function CreateFromTemplateFile($file) {
        if (!is_file($file)) {
            throw new MailUtils_Exception("File not exists '{$file}'");
        }
        return new self($file);
    }

    private $_tplLetter;

    /**
     * Doc
     * 
     * @var array
     */
    private $_assignsArray = array();

    /**
     * Doc
     *
     * @var array
     */
    private $_emails = array();

    private $_emailFrom;

    /**
     * Doc
     *
     * @var array
     */
    private $_attachments = array();

    private $_template;

    /**
     * Создать SmartySender
     *
     * @param string $tplLetter
     *
     * @see CreateFromTemplateFile()
     * @see CreateFromTemplateData()
     */
    public function __construct($tplLetter) {
        $this->_tplLetter = $tplLetter;
    }

    /**
     * Отправить письмо
     */
    public function send() {
        if (!isset($this->_assignsArray['signature'])) {
            // автоматически пытаемся найти подпись
            $signatureFile = dirname($this->_tplLetter).'/signature.html';
            if (file_exists($signatureFile)) {
                $this->assign('signature', file_get_contents($signatureFile));
            }
        }

        $body = MailUtils_Smarty::FetchSmarty($this->_tplLetter, $this->_assignsArray);

        $subject = '';

        if (preg_match("/Subject\:\s*(.+?)\n/iu", $body, $r)) {
            $subject = trim(strip_tags($r[1]));
            $body = trim(preg_replace("/Subject\:\s*(.+?)\n/iu", '', $body, 1));
        }

        if ($this->_template) {
            $body = str_replace('[content]', $body, $this->_template);
        }

        foreach ($this->_emails as $email) {
            $letter = new MailUtils_Letter(
                $this->_emailFrom,
                $email,
                $subject,
                $body
            );

            // вкладываем вложения
            foreach ($this->_attachments as $attach) {
                $letter->addAttachment(
                    $attach['data'],
                    $attach['name'],
                    $attach['type']
                );
            }

            // @todo option, doctype?
            $letter->setBodyType('text/html');

            // отправляем письмо
            $letter->send();
        }
    }

    public function assign($key, $value) {
        $this->_assignsArray[$key] = $value;
    }

    /**
     * Doc
     *
     * @return array
     */
    public function getAssigns() {
        return $this->_assignsArray;
    }

    /**
     * Doc
     *
     * @return array
     */
    public function setAssigns($assignsArray) {
        $this->_assignsArray = $assignsArray;
    }

    /**
     * Добавить массив Email-ов или один email-адрес
     *
     * @param mixed $email
     */
    public function addEmail($email) {
        // @todo check & doublicates!
        if (is_array($email)) {
            foreach ($email as $e) {
                // внимание, рекурсия!
                $this->addEmail($e);
            }
        } else {
            $this->_emails[] = $email;
        }
    }

    /**
     * Очистить список емейлов для отправки
     */
    public function clearEmails() {
        $this->_emails = array();
    }

    public function setEmailFrom($email) {
        // @todo check & doublicates!
        $this->_emailFrom = $email;
    }

    public function setTemplate($template = false) {
        $this->_template = $template;
    }

    public function getTemplate() {
        return $this->_template;
    }

    public function clearTemplate() {
        $this->_template = false;
    }

    public function addAttachment($data, $name = 'Attachment', $type = "application/octet-stream") {
        $this->_attachments[] = array(
        'type' => $type,
        'data' => $data,
        'name' => $name
        );
    }

}