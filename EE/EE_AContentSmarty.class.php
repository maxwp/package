<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Content for Web Smarty
 */
class EE_AContentSmarty extends EE_AContent implements EE_IContent {

    public function __construct() {
        // первый раз определяем есть ли filehtml
        $filePHP = new ReflectionClass($this);
        $fileHTML = str_replace('.php', '.html', $filePHP->getFileName());
        if (file_exists($fileHTML)) {
            $this->_filehtml = $fileHTML;
        }
    }

    /**
     * Получить control-значение.
     * Метод проверяет, было ли ранее установлено control-value и
     * возвращает его значение.
     * Иначе работает как getArgumentSecure()
     *
     * @param string $controlName
     *
     * @return mixed
     *
     * @throws EE_Exception
     */
    public function getControlValue($controlName, $argType = false) {
        $argType = strtolower($argType);

        $controlName = trim($controlName);
        if (!$controlName) {
            throw new EE_Exception("Empty control value name. Nothing to get");
        }
        if (isset($this->_controlArray[$controlName]) && $argType != 'file') {
            return $this->_controlArray[$controlName];
        }
        $value = EE::Get()->getRequest()->getArgumentSecure($controlName, $argType);
        if ($value && !is_array($value)) {
            $value = trim($value);
        }
        return $value;
    }

    /**
     * Задать control-значение.
     * Метод записывает control-value во внутренний буфер текущиего контента,
     * а затем просто делает setValue() его.
     *
     * @param string $controlName
     * @param mixed $controlValue
     *
     * @throws EE_Exception
     */
    public function setControlValue($controlName, $controlValue) {
        if (is_object($controlName)) {
            throw new EE_Exception("Empty control name must be a string");
        }

        if ($controlValue && is_object($controlValue)) {
            throw new EE_Exception("Empty control value must be a string");
        }

        $controlName = trim($controlName);
        if (!$controlName) {
            throw new EE_Exception("Empty control value name. Nothing to set");
        }

        $this->_controlArray[$controlName] = $controlValue;

        $this->setValue('control_'.$controlName, htmlspecialchars($controlValue));
    }

    /**
     * Удалить заданное ранее control-значение
     *
     * @param string $controlName
     *
     * @return string
     */
    public function unsetControlValue($controlName) {
        $controlName = trim($controlName);
        if (!$controlName) {
            throw new EE_Exception("Empty control value name. Nothing to unset");
        }
        unset($this->_controlArray[$controlName]);
    }

    public function unsetControlValueArray() {
        $this->_controlArray = [];
    }

    public function setValueSecure($key, $value) {
        $this->setValue($key, htmlspecialchars($value));
    }

    public function process() {

    }

    /**
     * Отрисовать контент (отрендерить в html-код).
     *
     * @return string
     */
    public function render() {
        // берем все аргументы и экранируем их, передаем в качестве control_xxx
        // я делаю это перед вызовом process(), это позволит внутри process()
        // менять эти control-значения или стирать их
        $argumentArray = EE::Get()->getRequest()->getArgumentArray();
        foreach ($argumentArray as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $a['control_'.$key] = htmlspecialchars($value);
        }

        parent::render();

        // если html-файла нет - то нет смысла продолжать
        $file = $this->getValue('filehtml');
        if (!$file) {
            return '';
        }

        // рендерим контент
        EV::GetInternal()->notify('EE:content.render:before', $this, '');

        // получаем все параметры, которые надо передать в smarty
        $html = EE_Smarty::Get()->fetch($file, $this->getValueArray());

        // генерируем событие afterRender
        EV::GetInternal()->notify('EE:content.render:after', $this, $html);

        // достаем новый html из события
        // @todo
        //$html = $event->getRenderHTML();

        return $html;
    }

    public function reset() {
        parent::reset();

        $this->unsetControlValueArray();

        // заполняем только одно поле - filehtml
        if ($this->_filehtml) {
            $this->setValue('filehtml', $this->_filehtml);
        }
    }

    private $_controlArray = [];

    private $_filehtml = false;

}