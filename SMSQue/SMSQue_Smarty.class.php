<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package SMSQue
 */
class SMSQue_Smarty extends Smarty_FileFetch {

    public static function FetchSmarty($file, $assignsArray, $compilePath = false) {
        return parent::FetchSmarty($file, $assignsArray, __DIR__.'/compile/');
    }

}