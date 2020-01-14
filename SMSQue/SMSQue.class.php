<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2015 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Отложенный отправщик почты.
 * Складывает письма в таблицу в базе,
 * а затем отдельным скриптом в cron'e
 * можно выполнять отправку по очереди.
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   SMSQue
 */
class SMSQue {

    /**
     * Создать SmartySender на основе шаблон-файла
     *
     * @return SMSQue
     */
    public static function CreateSMSFromTemplateFile($file) {
        if (!is_file($file)) {
            throw new SMSQue_Exception("File not exists '{$file}'");
        }
        return new self($file);
    }

    public function __construct($tplLetter) {
        $this->_tplLetter = $tplLetter;
    }

    /**
     * Отправить SMS
     */
    public function send($startDate = false) {
        $mysql = ConnectionManager::Get()->getConnectionDatabase();

        $body = SMSQue_Smarty::FetchSmarty($this->_tplLetter, $this->getValueArray());

        // чистим хлам в SMS
        $body = str_replace("\n", ' ', $body);
        $body = str_replace("\r", ' ', $body);
        $body = str_replace("\t", ' ', $body);
        $body = str_replace(' ', ' ', $body);
        $body = str_replace(' ', ' ', $body);
        $body = str_replace(' ', ' ', $body);

        $status = 0;

        foreach ($this->_toArray as $to) {
            try {
                $mysql->transactionStart();

                $cdate = date('Y-m-d H:i:s');

                if (!$startDate) {
                    $startDate = $cdate;
                }

                $from = $mysql->escapeString($this->_from);
                $to = $mysql->escapeString($to);
                $body = $mysql->escapeString($body);

                $mysql->query("
                INSERT INTO smsque
                (cdate, sdate, status, `from`, `to`, body)
                VALUES(
                '$cdate', '$startDate', '$status', '$from', '$to', '$body'
                )
                ");

                $mysql->transactionCommit();
            } catch (Exception $e) {
                $mysql->transactionRollback();
                throw $e;
            }
        }
    }

    public function setValue($key, $value) {
        $this->_valueArray[$key] = $value;
    }

    /**
     * Doc
     *
     * @return array
     */
    public function getValueArray() {
        return $this->_valueArray;
    }

    /**
     * Doc
     *
     * @return array
     */
    public function setValueArray($valueArray) {
        $this->_valueArray = $valueArray;
    }

    /**
     * Добавить массив Email-ов или один email-адрес
     *
     * @param string $email
     */
    public function addTo($to) {
        $this->_toArray[] = $to;
    }

    /**
     * Очистить список емейлов для отправки
     */
    public function clearToArray() {
        $this->_toArray = array();
    }

    public function setFrom($from) {
        $this->_from = $from;
    }

    /**
     * Выполнить обработку очереди
     *
     * @param SMSQue_ISender $sender
     * @param int $limit
     */
    public static function ProcessQue(SMSQue_ISender $sender, $limit = 50) {
        $mysql = ConnectionManager::Get()->getConnectionDatabase();

        $q = $mysql->query("
        SELECT *
        FROM smsque
        WHERE 1=1
        AND status=0
        AND sdate <= '".date('Y-m-d H:i:s')."'
        ORDER BY id ASC
        LIMIT $limit
        ");
        while ($x = $mysql->fetch($q)) {
            // отправляем письмо
            try {
                $result = $sender->send($x['from'], $x['to'], $x['body']);
            } catch (Exception $e) {
                $result = 'sender exception '.$e->getMessage();
            }

            // помечаем письмо как отправленное
            $result = $mysql->escapeString($result);
            $mysql->query("
            UPDATE smsque
            SET status=1, pdate='".date('Y-m-d H:i:s')."', result='$result'
            WHERE id=$x[id] LIMIT 1
            ");
        }
    }

    private $_tplLetter;

    private $_valueArray = array();

    private $_toArray = array();

    private $_from;

}