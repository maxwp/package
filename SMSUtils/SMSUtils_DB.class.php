<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2016 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Sync DB for SMSUtils
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   SMSUtils
 */
class SMSUtils_DB implements Events_IEventObserver {

    public function notify(Events_Event $event) {
        $event;

        $table = SQLObject_Config::Get()->addClass('SMSUtils_XTurbosmsuaQue', 'smsutils_que');
        $table->addField('id', "int(11)", 'auto_increment');
        $table->addIndexPrimary('id');
        $table->addField('cdate', "datetime");
        $table->addField('status', "tinyint(1)");
        $table->addField('pdate', "datetime");
        $table->addField('sdate', "datetime");
        $table->addField('sender', "varchar(255)");
        $table->addField('to', "varchar(255)");
        $table->addField('content', "text");
        $table->addField('result', "text");
        $table->addField('trycnt', 'int(3)');
        $table->addField('userid', "int(11)");
        // indexes
        $table->addIndex('status', 'index_status');
        $table->addIndex('pdate', 'index_pdate');
        $table->addIndex('cdate', 'index_cdate');
    }

}