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
 * @package   MailUtils
 */
class MailUtils_SenderQueDB implements MailUtils_ISender {

    public function __construct() {

    }

    public function send(MailUtils_Letter $letter, $startDate = false) {
        $mysql = ConnectionManager::Get()->getConnectionDatabase();

        try {
            $mysql->transactionStart();

            $cdate = date('Y-m-d H:i:s');

            if (!$startDate) {
                $startDate = $cdate;
            }

            $status = 0;
            $ip = isset($_SERVER['HTTP_X_REAL_IP'])?$_SERVER['HTTP_X_REAL_IP']:@$_SERVER['REMOTE_ADDR'];
            $subject = $letter->getSubject();
            $from = $letter->getEmailFrom();
            $to = $letter->getEmailTo();
            $cc = $letter->getCc();
            $body = $letter->getBody();
            $bodyType = $letter->getBodyType();

            $ip = $mysql->escapeString($ip);
            $subject = $mysql->escapeString($subject);
            $from = $mysql->escapeString($from);
            $to = $mysql->escapeString($to);
            $cc = $mysql->escapeString($cc);
            $body = $mysql->escapeString($body);
            $bodyType = $mysql->escapeString($bodyType);

            $mysql->query("
            INSERT INTO mailutils_que
            (cdate, sdate, status, ip, subject, `from`, `to`, cc, body, bodytype)
            VALUES(
            '$cdate', '$startDate', '$status', '$ip', '$subject', '$from', '$to', '$cc', '$body', '$bodyType'
            )
            ");

            $queID = $mysql->getLastInsertID();

            // сохранение attachment-ов отдельно
            if ($attachments = $letter->getAttachments()) {
                foreach ($attachments as $x) {
                    $type = $x['type'];
                    $name = $x['name'];

                    $file = md5($x['data']);

                    file_put_contents(__DIR__.'/media/'.$file, $x['data']);

                    $type = $mysql->escapeString($type);
                    $name = $mysql->escapeString($name);
                    $file = $mysql->escapeString($file);

                    $mysql->query("
                    INSERT INTO mailutils_attachment
                    (cdate, queid, type, name, file)
                    VALUES(
                    '$cdate', '$queID', '$type', '$name', '$file'
                    )
                    ");
                }
            }

            $mysql->transactionCommit();

            return $queID;
        } catch (Exception $e) {
            $mysql->transactionRollback();
            throw $e;
        }
    }

    /**
     * Выполнить обработку очереди.
     *
     * @param int $limit
     */
    public static function ProcessQue($limit = 50, $clearInterval = 168) {
        new self();

        $result = array();
        $sender = MailUtils_Config::Get()->getSender();

        if ($sender instanceof MailUtils_SenderQueDB) {
            throw new MailUtils_Exception("Can not send que with que sender");
        }

        $mysql = ConnectionManager::Get()->getConnectionDatabase();
        $q = $mysql->query("
        SELECT *
        FROM mailutils_que
        WHERE 1=1
        AND status=0
        AND sdate <= '".date('Y-m-d H:i:s')."'
        ORDER BY id ASC
        LIMIT $limit
        ");
        while ($x = $mysql->fetch($q)) {
            // складываем письмо...
            $letter = new MailUtils_Letter($x['from'], $x['to'], $x['subject'], $x['body'], $x['cc']);
            $letter->setBodyType($x['bodytype']);

            // добавляем attachment-ы
            $q_attachment = $mysql->query("SELECT * FROM mailutils_attachment WHERE queid=$x[id] AND cdate='$x[cdate]'");
            while ($attachment = $mysql->fetch($q_attachment)) {
                $letter->addAttachment(
                    file_get_contents(__DIR__.'/media/'.$attachment['file']),
                    $attachment['name'],
                    $attachment['type']
                );
            }

            // собираем письмо
            $letter->make();

            // отправляем письмо...
            $letter->send();

            // помечаем письмо как отправленное
            $mysql->query("
            UPDATE mailutils_que SET status=1, pdate='".date('Y-m-d H:i:s')."' WHERE id=$x[id] LIMIT 1
            ");
        }
        //if (is_numeric($clearInterval)) self::ClearQueue($clearInterval);

        return $result;
    }

    /**
     * Очистить отработанную очередь.
     * $inteval указывается в часах и указывает максимальный
     * срок хранения обработанной записи с момента её обработки.
     * Если указать 0, то удалятся все уже
     * отработанные записи очереди.
     *
     * @param int $inteval
     */
    public static function ClearQueue($interval = 168) {
        $interval = (int) $interval;

        try {
            SQLObject::TransactionStart();

            $que = new MailUtils_XQue();

            // Ramm: формирование даты в PHP -
            // - не оптимизация запроса.
            // При вставке в очередь дата берётся из пхп.
            // Посему и тут мы должны формировать в PHP
            // дабы не напороться на рассинхронизаци часовых поясов
            $date = date('Y-m-d H:i:s', time() - $interval*3600);

            $attachments = new MailUtils_XQueAttachment();
            $attachments->addWhereQuery(
                '(`queid` IN (SELECT `id` FROM `'.$que->getTablename().'`
                WHERE `status`=\'1\'' . ($interval?" AND `pdate` <= '$date'":'').'))'
            );
            while ($attachment = $attachments->getNext()) {
                unlink(__DIR__.'/media/mailutils_que/'.$attachment->getFile());
                $attachment->delete();
            }

            SQLObject_Config::Get()->getConnectionDatabase()->query(
                "DELETE FROM `{$que->getTablename()}`
                WHERE `status`='1'" . ($interval?" AND `pdate` <= '$date'":'')
            );

            SQLObject::TransactionCommit();
        } catch (Exception $e) {
            SQLObject::TransactionRollback();

            if (PackageLoader::Get()->getMode('debug')) {
                print "Exception: {$e->getMessage()}\n";
            }
        }
    }

}