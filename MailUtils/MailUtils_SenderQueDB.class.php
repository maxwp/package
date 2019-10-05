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
        if (PackageLoader::Get()->getMode('build')) {
            if (!is_dir(__DIR__.'/media/mailutils_que/')) {
                mkdir(__DIR__.'/media/');
                mkdir(__DIR__.'/media/mailutils_que/');
            }
        }
    }

    public function send(MailUtils_Letter $letter, $startDate = false) {
        try {
            SQLObject::TransactionStart();
            $cdate = date('Y-m-d H:i:s');

            if (!$startDate) {
                $startDate = $cdate;
            }

            // $logEmails = MailUtils_Config::Get()->getLogEmails();
            $logEmails[] = $letter->getEmailTo();
            foreach ($logEmails as $email) {
                $que = new MailUtils_XQue();
                $que->setCdate($cdate);
                $que->setSdate($startDate);
                $que->setStatus(0); // не отправлено
                $que->setIp(isset($_SERVER['HTTP_X_REAL_IP'])?$_SERVER['HTTP_X_REAL_IP']:@$_SERVER['REMOTE_ADDR']);
                $que->setSubject($letter->getSubject());
                $que->setFrom($letter->getEmailFrom());
                $que->setTo($email);
                $que->setCc($letter->getCc());
                $que->setBody($letter->getBody());
                $que->setBodytype($letter->getBodyType());
                $que->setEventid($letter->getEventid());
                $que->insert();

                // сохранение attachment-ов отдельно
                if ($attachments = $letter->getAttachments()) {
                    foreach ($attachments as $x) {
                        $att = new MailUtils_XQueAttachment();
                        $att->setQueid($que->getId());
                        $att->setCdate($cdate);
                        $att->setType($x['type']);
                        $att->setName($x['name']);

                        $file = md5($x['data']);
                        $att->setFile($file);

                        file_put_contents(__DIR__.'/media/mailutils_que/'.$file, $x['data']);

                        $att->insert();
                    }
                }
            }
            SQLObject::TransactionCommit();
        } catch (Exception $e) {
            SQLObject::TransactionRollback();
            throw $e;
        }
    }

    /**
     * Выполнить обработку очереди.
     *
     * @param int $limit
     */
    public static function ProcessQue($limit = 50, $clearInterval = 168, $package = 'SQLObject') {
        new self($package);
        $result = array();
        $sender = MailUtils_Config::Get()->getSender();

        if ($sender instanceof MailUtils_SenderQueDB) {
            throw new MailUtils_Exception("Can not send que with que sender");
        }

        $que = new MailUtils_XQue();
        $que->addWhere('sdate', date('Y-m-d H:i:s'), '<=');
        $que->setStatus(0);
        $que->setLimitCount($limit);
        $que->setOrder('id', 'DESC');
        while ($x = $que->getNext()) {

            if (MailUtils_Config::Get()->isVerboseMode()) {
                print 'Process send mail #'.$x->getId().' from '.$x->getFrom().' to '.$x->getTo()."\n";
            }
            // складываем письмо...
            $letter = new MailUtils_Letter($x->getFrom(), $x->getTo(), $x->getSubject(), $x->getBody(), $x->getCc());
            $letter->setBodyType($x->getBodytype());

            // добавляем attachment-ы
            $attachments = new MailUtils_XQueAttachment();
            $attachments->setQueid($x->getId());
            $attachments->setCdate($x->getCdate());
            while ($attachment = $attachments->getNext()) {
                $letter->addAttachment(
                    file_get_contents(__DIR__.'/media/mailutils_que/'.$attachment->getFile()),
                    $attachment->getName(),
                    $attachment->getType()
                );
            }

            // собираем письмо
            $letter->make();

            // отправляем письмо...
            $result[$x->getFrom()] = $letter->send();

            $config = new XShopEventIMAPConfig();
            $config->setSmtpnosend(1);
            $config->setActive(1);
            $config->setEmail($x->getFrom());
            $config->setLimitCount(1);
            $cf = $config->getNext();
            // обновляем информацию в базе
            if (!$cf || $result[$x->getFrom()]['status'] == 'success') {
                if ($result[$x->getFrom()]['sdate']) {
                    $sdate = $result[$x->getFrom()]['sdate'];
                } else {
                    $sdate = date('Y-m-d H:i:s');
                }
                $x->setStatus(1);
                $x->setPdate($sdate);
                $x->update();

                $eventID = $x->getEventid();
                if ($eventID) {
                    $event = EventService::Get()->getEventByID($eventID);
                    $event->setCdate($x->getPdate());
                    $event->update();
                }

                if (MailUtils_Config::Get()->isVerboseMode()) {
                    print 'Process send mail #' . $x->getId() . ' from ' .
                        $x->getFrom() . ' to ' . $x->getTo() . " ... OK\n";
                }
            } else {
                if (MailUtils_Config::Get()->isVerboseMode()) {
                    print 'Process send mail #' . $x->getId() . ' from ' .
                        $x->getFrom() . ' to ' . $x->getTo() . " ... ERROR - FAIL SMTP\n";
                }
            }

        }
        if (is_numeric($clearInterval)) self::ClearQueue($clearInterval);

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