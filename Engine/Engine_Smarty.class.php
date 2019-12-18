<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2016 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Smarty template engine for wpp Engine
 *
 * @author     Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright  WebProduction
 * @package    Engine
 * @subpackage Smarty
 */
class Engine_Smarty {

    /**
     * Через Smarty обработать файл и выдать html-код
     *
     * @param string $file
     * @param array $assignArray
     *
     * @return string
     */
    public function fetch($file, $assignArray) {
        $smarty = $this->getSmarty();
        $smarty->assignArray($assignArray, false); // no merge
        return $smarty->fetch($file);
    }

    /**
     * Выполнить обработку $html и вернуть строку HTML.
     *
     * @param string $html
     * @param array $assignArray
     *
     * @return string
     */
    public function fetchString($html, $assignArray, $ex = false) {
        $file = $this->getComplileDirectory().md5($html).'.tpl';
        file_put_contents($file, $html, LOCK_EX);

        $smarty = $this->getSmarty();
        $smarty->assignArray($assignArray, false); // no merge

        $html = $smarty->fetch($file, $ex);
        return $html;
    }

    /**
     * Получить объект шаблонизатора Smarty
     *
     * @return Smarty
     */
    public function getSmarty() {
        return $this->_smarty;
    }

    public function __construct() {
        // подключаем Smarty
        include_once(dirname(__FILE__).'/../Smarty/include.php');

        // инициируем Smarty внутри (аггрегация Smarty)
        $this->_smarty = new Smarty();
        $this->_smarty->compile_dir = __DIR__.'/compile/';
    }

    public function getComplileDirectory() {
        return $this->getSmarty()->compile_dir;
    }

    public function setCompileDirectory($dir) {
        $this->getSmarty()->compile_dir = $dir;
    }

    /**
     * Внутренний объект Smarty
     *
     * @var Smarty
     */
    private $_smarty = null;

}