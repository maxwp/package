<?php
/**
 * WebProduction Packages
 *
 * Copyright (C) 2007-2015 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Реализация отправщика почты через SMTP-relay-сервер
 *
 * @author    Maxim Miroshnichenko
 * @copyright WebProduction
 * @package   MailQue
 */
class MailQue_SenderSMTP implements MailQue_ISender {

    public function __construct(
        $email, $server, $port, $login, $password, $tls = false, $sent = false, $imapConfig = false, $smtpnosend = false
    ) {
        $this->_email = $email;
        $this->_server = $server;
        $this->_port = $port;
        $this->_login = $login;
        $this->_password = $password;
        $this->_tls = $tls;
        $this->_sent = $sent;
        $this->_imapConfig = $imapConfig;
        $this->_smtpnosend = $smtpnosend;
    }

    public function send(MailQue_Letter $letter) {
        $result = array(
            'login' => $this->_login,
            'status' => 'success'
        );

        if (!$this->_smtp) {
            $smtp = new MailQue_SMTP();
            if ($this->_port == '465') {
                $host = $this->_tls ? $this->_server : 'ssl://'.$this->_server;
            } else {
                $host = $this->_server;
            }

            $smtp->Connect($host, $this->_port);
            $smtp->Hello();
            if ($this->_tls) {
                $imapConfig = $this->_imapConfig;
                $smtp->StartTLS($imapConfig['disablessl']);
                $smtp->Hello();
            }
            if (!$smtp->Authenticate($this->_login, $this->_password)) {
                $result = array(
                    'login' => $this->_login,
                    'status' => 'error'
                );
                if (!$this->_smtpnosend) {
                    $mail = new MailQue_SenderMail();
                    $mail->send($letter);
                }
            }
            $this->_smtp = $smtp;
        }

        $smtp = $this->_smtp;

        if (!$letter->getEmailFrom()) {
            $letter->setEmailFrom($this->_email);
        }
        $content = $letter->make(true); // full

        // отправка через relay
        $smtp->Reset();

        $emailFrom = $letter->getEmailFrom();
        if (preg_match("/\<(.+?)\>/ius", $emailFrom, $r)) {
            $emailFrom = $r[1];
        }

        if (!$emailFrom) {
            $emailFrom = $this->_email;
        }

        $smtp->Mail($emailFrom);
        if ($smtp->Recipient($letter->getEmailTo())) {
            $smtp->Data($content);
        }

        $result['sdate'] = date('Y-m-d H:i:s');

        if ($this->_sent) {
            try {
                $mailbox = $this->_imapConfig;
                $port = $mailbox['port'];
                $optionString = $mailbox['optionString'];
                $disablegssapi = $mailbox['disablegssapi'];
                if (!$port) {
                    $port = 143;
                }
                if (!$optionString) {
                    $optionString = '/novalidate-cert';
                }

                if ($disablegssapi) {
                    $disableAuthentificator = 'GSSAPI';
                } else {
                    $disableAuthentificator = 'PLAIN';
                }
                $this->_imapRef = '{'.$mailbox['host'].':'.$port.$optionString.'}';
                $this->_imapMailboxCurrent = $mailbox['username'];
                $this->_imapConnection = imap_open(
                    $this->_imapRef,
                    $this->_imapMailboxCurrent,
                    $mailbox['password'],
                    null,
                    1,
                    array('DISABLE_AUTHENTICATOR' => $disableAuthentificator) // специальных hack против gmail
                );

                imap_append(
                    $this->_imapConnection,
                    $this->_imapRef . $this->_getSent(),
                    //$this->_imapMailboxCurrent . $this->_getSent(),
                    $content . "\r\n",
                    "\\Seen"
                );

                $result['sdate'] = date('Y-m-d H:i:s');

                imap_close($this->_imapConnection);
            } catch (Exception $ex) {

            }
        }

        return $result;
    }


    protected function _getSent() {
        if ($this->_getFolders()) {
            foreach ($this->_getFolders() as $folder) {
                if (strtolower($folder) === "sent" || strtolower($folder) === "gesendet") {
                    return $folder;
                }
            }
        }
        // no sent folder found? create one
        $this->_addFolder("Sent");

        return 'Sent';
    }

    /**
     * Returns all available folders
     *
     * @return array with foldernames
     */
    protected function _getFolders() {
        $folders = imap_list($this->_imapConnection, $this->_imapMailboxCurrent, "*");
        return str_replace($this->_imapMailboxCurrent, "", $folders);
    }

    protected function _addFolder($name, $subscribe = false) {
        $mailbox = $this->_imapMailboxCurrent;
        $success = @imap_createmailbox($this->_imapConnection, $mailbox . $name);
        if ($success && $subscribe) {
            $success = @imap_subscribe($this->_imapConnection, $mailbox . $name);
        }
        return $success;
    }

    /**
     * Объект SMTP
     *
     * @var MailQue_SMTP
     */
    private $_smtp = null;

    private $_email;

    private $_login;

    private $_password;

    private $_server;

    private $_port;

    private $_tls;

    private $_sent;

    private $_imapConfig;

    private $_imapConnection;

    private $_imapMailboxCurrent;

    private $_imapRef;

    private $_smtpnosend;

}