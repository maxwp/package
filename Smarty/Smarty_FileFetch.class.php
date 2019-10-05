<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2011 WebProduction <webproduction.com.ua>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU LESSER GENERAL PUBLIC LICENSE
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
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