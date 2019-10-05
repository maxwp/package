<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2016 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Sync DB for MailUtils_SenderQueDB
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   MailUtils
 */
class MailUtils_DB implements Events_IEventObserver {

    public function notify(Events_Event $event) {
        $event;

        $table = SQLObject_Config::Get()->addClass('MailUtils_XQue', 'mailutils_que');
        $table->addField('id', "int(11)", 'auto_increment');
        $table->addIndexPrimary('id');
        $table->addField('cdate', "datetime");
        $table->addField('status', "tinyint(1)");
        $table->addField('sdate', "datetime");
        $table->addField('pdate', "datetime");
        $table->addField('ip', "varchar(15)");//added by Ramm 06.04.2012
        $table->addField('from', "varchar(255)");
        $table->addField('to', "varchar(255)");
        $table->addField('cc', "varchar(255)");
        $table->addField('subject', "varchar(255)");
        $table->addField('body', "longtext");
        $table->addField('bodytype', "varchar(255)");
        $table->addField('eventid', "int(11)");
        // indexes
        $table->addIndex('status', 'index_status');

        $table = SQLObject_Config::Get()->addClass('MailUtils_XQueAttachment', 'mailutils_queattachment');
        $table->addField('id', "int(11)", 'auto_increment');
        $table->addIndexPrimary('id');
        $table->addField('queid', "int(11)");
        $table->addField('cdate', "datetime");
        $table->addField('name', "varchar(255)");
        $table->addField('type', "varchar(255)");
        $table->addField('file', "varchar(255)");
        // indexes
        $table->addIndex('queid', 'index_queid');
    }

}