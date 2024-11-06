<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Реализация отправщика почты через обычную php-функцию mail()
 *
 * @author    Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package   MailQue
 */
class MailQue_SenderMail implements MailQue_ISender {

    public function send(MailQue_Letter $letter) {
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
    }

}