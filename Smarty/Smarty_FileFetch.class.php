<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * @deprecated
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package Smarty
 */
class Smarty_FileFetch extends Smarty {

    /**
     * Через Smarty обработать файл и выдать html-код
     *
     * @deprecated
     *
     * @param string $file
     * @param array $assignsArray
     * @return string
     */
    public static function FetchSmarty($file, $assignsArray, $compilePath = false) {
        if ($compilePath) {
            self::GetSmarty()->compile_dir = $compilePath;
        }

        self::GetSmarty()->clear_all_assign();

        foreach ($assignsArray as $key => $value) {
            self::GetSmarty()->assign($key, $value);
        }
        return self::$_Smarty->fetch($file);
    }

    /**
     * @return Engine_Smarty
     */
    public static function GetSmarty() {
        if (empty(self::$_Smarty)) {
            self::$_Smarty = new self();
            self::$_Smarty->compile_dir = __DIR__.'/compile/';

            // @todo: what do with force-compile?
            //self::$_Smarty->force_compile = Engine::Get()->getForceCompile();
        }

        return self::$_Smarty;
    }

    /**
     * @var Engine_Smarty
     */
    private static $_Smarty = null;

}