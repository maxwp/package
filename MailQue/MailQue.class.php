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
 * @package   MailQue
 */
class MailQue {

    /**
     * Создать SmartySender на основе готового кода шаблона
     *
     * @return MailQue
     */
    /*public static function CreateFromTemplateData($data) {
        $data = trim($data);
        if (!$data) {
            throw new MailQue_Exception('Empty data');
        }

        // сохраняем во временный файл
        $file = __DIR__.'/compile/'.md5($data).'.html';
        file_put_contents($file, $data, LOCK_EX);

        return new self($file);
    }*/

    /**
     * Создать SmartySender на основе шаблон-файла
     *
     * @return MailQue
     */
    public static function CreateFromTemplateFile($file) {
        if (!is_file($file)) {
            throw new MailQue_Exception("File not exists '{$file}'");
        }
        return new self($file);
    }

    public function __construct($tplLetter) {
        $this->_tplLetter = $tplLetter;
    }

    /**
     * Отправить письмо
     */
    public function send($startDate = false) {
        $mysql = ConnectionManager::Get()->getConnectionDatabase();

        $body = MailQue_Smarty::FetchSmarty($this->_tplLetter, $this->getValueArray());

        $subject = '';

        if (preg_match("/Subject\:\s*(.+?)\n/iu", $body, $r)) {
            $subject = trim(strip_tags($r[1]));
            $body = trim(preg_replace("/Subject\:\s*(.+?)\n/iu", '', $body, 1));
        }

        $status = 0;
        $ip = isset($_SERVER['HTTP_X_REAL_IP'])?$_SERVER['HTTP_X_REAL_IP']:@$_SERVER['REMOTE_ADDR'];
        $bodyType = 'text/html';

        foreach ($this->_emailArray as $to) {
            try {
                $mysql->transactionStart();

                $cdate = date('Y-m-d H:i:s');

                if (!$startDate) {
                    $startDate = $cdate;
                }

                $ip = $mysql->escapeString($ip);
                $subject = $mysql->escapeString($subject);
                $from = $mysql->escapeString($this->_emailFrom);
                $to = $mysql->escapeString($to);
                $body = $mysql->escapeString($body);
                $bodyType = $mysql->escapeString($bodyType);

                $mysql->query("
                INSERT INTO mailque
                (cdate, sdate, status, ip, subject, `from`, `to`, body, bodytype)
                VALUES(
                '$cdate', '$startDate', '$status', '$ip', '$subject', '$from', '$to', '$body', '$bodyType'
                )
                ");

                $queID = $mysql->getLastInsertID();

                // сохранение attachment-ов отдельно
                foreach ($this->_attachmentArray as $attach) {
                    $type = $attach['type'];
                    $name = $attach['name'];

                    $file = md5($attach['data']);

                    file_put_contents(__DIR__.'/media/'.$file, $attach['data']);

                    $type = $mysql->escapeString($type);
                    $name = $mysql->escapeString($name);
                    $file = $mysql->escapeString($file);

                    $mysql->query("
                    INSERT INTO mailque_attachment
                    (cdate, queid, type, name, file)
                    VALUES(
                    '$cdate', '$queID', '$type', '$name', '$file'
                    )
                    ");
                }

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
    public function addEmail($email) {
        $this->_emailArray[] = $email;
    }

    /**
     * Очистить список емейлов для отправки
     */
    public function clearEmailArray() {
        $this->_emailArray = array();
    }

    public function setEmailFrom($email) {
        $this->_emailFrom = $email;
    }

    public function addAttachment($data, $name = 'Attachment', $type = "application/octet-stream") {
        $this->_attachmentArray[] = array(
        'type' => $type,
        'data' => $data,
        'name' => $name
        );
    }

    /**
     * Выполнить обработку очереди
     *
     * @param MailQue_ISender $sender
     * @param int $limit
     */
    public static function ProcessQue(MailQue_ISender $sender, $limit = 50) {
        $mysql = ConnectionManager::Get()->getConnectionDatabase();

        $q = $mysql->query("
        SELECT *
        FROM mailque
        WHERE 1=1
        AND status=0
        AND sdate <= '".date('Y-m-d H:i:s')."'
        ORDER BY id ASC
        LIMIT $limit
        ");
        while ($x = $mysql->fetch($q)) {
            // складываем письмо...
            $letter = new MailQue_Letter($x['from'], $x['to'], $x['subject'], $x['body'], $x['cc']);
            $letter->setBodyType($x['bodytype']);

            // добавляем attachment-ы
            $q_attachment = $mysql->query("SELECT * FROM mailque_attachment WHERE queid=$x[id] AND cdate='$x[cdate]'");
            while ($attachment = $mysql->fetch($q_attachment)) {
                $letter->addAttachment(
                    file_get_contents(__DIR__.'/media/'.$attachment['file']),
                    $attachment['name'],
                    $attachment['type']
                );
            }

            // собираем письмо
            $letter->make();

            // отправляем письмо
            $sender->send($letter);

            // помечаем письмо как отправленное
            $mysql->query("
            UPDATE mailque SET status=1, pdate='".date('Y-m-d H:i:s')."' WHERE id=$x[id] LIMIT 1
            ");
        }
    }

        private $_tplLetter;

    /**
     * Doc
     *
     * @var array
     */
    private $_valueArray = array();

    /**
     * Doc
     *
     * @var array
     */
    private $_emailArray = array();

    private $_emailFrom;

    /**
     * Doc
     *
     * @var array
     */
    private $_attachmentArray = array();

}