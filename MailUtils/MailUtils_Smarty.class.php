<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2015 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package MailUtils
 */
class MailUtils_Smarty extends Smarty_FileFetch {

    public static function FetchSmarty($file, $assignsArray, $compilePath = false) {
        return parent::FetchSmarty($file, $assignsArray, __DIR__.'/compile/');
    }

}