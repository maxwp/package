<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Отложенный отправщик SMS через таблицу в базе.
 * Складывает SMS в таблицу в базе, а затем отдельным скриптом в cron'e
 * можно выполнять отправку по очереди.
 *
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 *
 * @copyright WebProduction
 *
 * @package MailUtils
 */
class SMSUtils_SenderQueDB implements SMSUtils_ISender {

    public function __construct($package = 'SQLObject') {
        PackageLoader::Get()->import($package);

        if (!class_exists('SoapClient')) {
            throw new SMSUtils_Exception('SOAPClient not found');
        }
    }

    public function send($sender, $destination, $text, $startDate = false) {
        try {
            SQLObject::TransactionStart();

            $userid = 0;
            try {
                $user = UserService::Get()->getUser();
                $userid = $user->getId();
            } catch (Exception $e) {

            }

            $que = new SMSUtils_XTurbosmsuaQue();
            $que->setCdate(date('Y-m-d H:i:s'));
            $que->setSdate($startDate);
            $que->setStatus(0); // не отправлено
            $que->setSender($sender);
            $que->setTo($destination);
            $que->setContent($text);
            $que->setUserId($userid);
            $que->insert();

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
    public static function ProcessQue(SMSUtils_ISender $sender, $limit = 40, $claerinterval = 168) {
        $que = new SMSUtils_XTurbosmsuaQue();
        $que->addWhere('sdate', date('Y-m-d H:i:s'), '<=');
        $que->setStatus(0);
        $que->setLimitCount($limit);

        $result = true;
        while ($x = $que->getNext()) {
            $senderSMS = $sender;

            $diffdate = DateTime_Object::DiffDay(
                DateTime_Object::Now()->setFormat("Y-m-d H:i:s"),
                DateTime_Object::FromString($x->getCdate())
            );
            if ($diffdate > 2) {
                $x->setPdate(date('Y-m-d H:i:s'));
                $x->setStatus(1);
                $x->setResult('time-send-over');
                $x->update();
            } else {
                $smsConfig = false;
                // сначала пытаемся найти подходящий сервис по формату номера телефона
                // если нету - то дефолтный
                $smsConfigs = new XShopEventSMSConfig();
                $smsConfigs->setActive(1);
                $smsConfigs->filterPhonestemplate('', '!=');
                while ($sm = $smsConfigs->getNext()) {
                    $formats = explode("\n", $sm->getPhonestemplate());
                    foreach ($formats as $format) {
                        $xCount = substr_count($format, 'X');
                        $number = str_replace('X', '', $format);

                        if (preg_match('/^'.$number.'[0-9]{'.$xCount.'}$/', $x->getTo())) {
                            $smsConfig = $sm;
                            break;
                        }
                    }
                    if ($smsConfig) {
                        break;
                    }
                }

                if ($smsConfig) {
                    $senderMethod = $smsConfig->getService();

                    $apiLogin = $smsConfig->getLogin();
                    $apiPassword = $smsConfig->getPassword();
                    $apiUrl = $smsConfig->getUrl();
                    $apiId = $smsConfig->getApiid();

                    if ($senderMethod == 'SMSUtils_SenderTurboSMSua') {
                        $senderSMS = new SMSUtils_SenderTurbosmsua($apiLogin, $apiPassword);
                    }
                    if ($senderMethod == 'SMSUtils_SenderSMSCru') {
                        $senderSMS = new SMSUtils_SenderSMSCru($apiLogin, $apiPassword);
                    }
                    if ($senderMethod == 'SMSUtils_SenderSMSCkz') {
                        $senderSMS = new SMSUtils_SenderSMSCkz($apiLogin, $apiPassword);
                    }
                    if ($senderMethod == 'SMSUtils_SenderWorldWide') {
                        $senderSMS = new SMSUtils_SenderWorldWide($apiLogin, $apiPassword);
                    }
                    if ($senderMethod == 'SMSUtils_SenderWebSMS') {
                        $senderSMS = new SMSUtils_SenderWebSMS($apiLogin, $apiPassword);
                    }
                    if ($senderMethod == 'SMSUtils_SenderSMSfly') {
                        $senderSMS = new SMSUtils_SenderSMSFly($apiLogin, $apiPassword);
                    }
                    if ($senderMethod == 'SMSUtils_SenderLife') {
                        $senderSMS = new SMSUtils_SenderLife($apiLogin, $apiPassword);
                    }
                    if ($senderMethod == 'SMSUtils_SenderAsteriskSMS') {
                        $senderSMS = new SMSUtils_SenderAsteriskSMS(
                            $apiLogin,
                            $apiPassword,
                            $apiUrl,
                            $apiId
                        );

                    }
                    if ($senderMethod == 'SMSUtils_SenderOpenVox') {
                        $senderSMS = new SMSUtils_SenderOpenVoxSMS(
                            $apiLogin,
                            $apiPassword,
                            $apiUrl,
                            $apiId
                        );
                    }
                    if ($senderMethod == 'SMSUtils_SenderSMSru') {
                        $senderSMS = new SMSUtils_SenderSMSru(
                            $apiLogin,
                            $apiPassword,
                            $apiUrl,
                            $apiId
                        );
                    }
                    if ($senderMethod == 'SMSUtils_SenderAlphaSMS') {
                        $senderSMS = new SMSUtils_SenderAlphaSMS(
                            $apiLogin,
                            $apiPassword,
                            $apiId
                        );
                    }
                }

                // отправляем sms
                if ($senderSMS instanceof SMSUtils_SenderAsteriskSMS) {
                    $result = $senderSMS->send($x->getUserid(), $x->getTo(), $x->getContent());
                } else {
                    $result = $senderSMS->send($x->getSender(), $x->getTo(), $x->getContent());
                }

                if ($result == "success") {
                    $x->setPdate(date('Y-m-d H:i:s'));
                    $x->setStatus(1);
                }
                // обновляем информацию в базе
                $x->setResult($result);
                $x->update();
            }
        }

        if (is_numeric($claerinterval)) {
            self::ClearQueue($claerinterval);
        }

        return $result;
    }

}