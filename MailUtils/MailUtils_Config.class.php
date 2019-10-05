<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2015 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Класс конфигурации MailUtils
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   MailUtils
 */
class MailUtils_Config {

    /**
     * Включен ли режим verbose
     *
     * @return boolean
     */
    public function isVerboseMode() {
        return $this->_verbose;
    }

    /**
     * Установить режим verbose
     *
     * @param bool $verbose
     */
    public function setVerboseMode($verbose) {
        $this->_verbose = $verbose;
    }

    /**
     * Задать настройки подключения к SMTP-серверу.
     * Утилитный метод:
     * Он автоматически создаст и заполнит sender типа smtp-relay
     *
     * @param string $server
     * @param string $login
     * @param string $password
     */
    public function setSMTPRelay($server, $port, $login, $password) {
        $sender = new MailUtils_SenderSMTP($server, $port, $login, $password);
        $this->setSender($sender);
    }

    /**
     * Получить объект непосредственно отправщика письма
     *
     * @return MailUtils_ISender
     */
    public function getSender() {
        if ($this->_sender instanceof MailUtils_ISender) {
            return $this->_sender;
        }

        $classname = $this->_sender;
        $this->_sender = new $classname();

        return $this->_sender;
    }

    /**
     * Задать mail-sender отправщика писем
     * (по умолчанию direct-отправка php-функцией mail())
     *
     * @param mixed $sender
     */
    public function setSender($sender) {
        $this->_sender = $sender;
    }

    /**
     * Получить объект MailUtils_Config
     *
     * @return MailUtils_Config
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    private function __construct() {
        // инициализация
        $this->setSender(new MailUtils_SenderMail());
    }

    private $_verbose;

    private $_sender;

    private static $_Instance = null;

}