<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2015 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Реализация отправщика почты через обычную php-функцию mail()
 *
 * @author    Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package   MailUtils
 */
class MailUtils_SenderMail implements MailUtils_ISender {

    public function send(MailUtils_Letter $letter, $startDate = false) {

        if (MailUtils_Config::Get()->isVerboseMode()) {
            print 'Process Sender MailUtils_SenderMail send to ' . $letter->getEmailTo()."\n";
        }

        if ($startDate) {
            throw new MailUtils_Exception('MailUtils_SenderMail does not support startDate');
        }

        $content = $letter->make(false); // without subject
        $content = explode("\n\n", $content, 2);
        
        $domain = explode('@', $letter->getEmailTo());
        if (checkdnsrr($domain[1], 'ANY')) {
            mail(
                $letter->getEmailTo(),
                $letter->makeSubjectEncoded(),
                $content[1],
                $content[0]
            );
        }

        $method = 'mail';

        $logArray = array(
            'from: '.$letter->getEmailFrom(),
            'to: '.$letter->getEmailTo(),
            'cc: '.$letter->getCc(),
            'subject: '.$letter->getSubject(),
            'date: '.$startDate ? $startDate : DateTime_Object::Now()->__toString(),
            'method: '.$method,
            'class: MailUtils_SenderMail',

        );

        LogService::Get()->add($logArray, 'sendmail');

        if (MailUtils_Config::Get()->isVerboseMode()) {
            print 'Process Sender MailUtils_SenderMail send to ' . $letter->getEmailTo() . " OK...\n";
        }
       
    }

}