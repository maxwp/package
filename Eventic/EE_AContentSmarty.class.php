<?php
/**
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 * @copyright WebProduction
 * @package EE
 */
class EE_AContentSmarty extends EE_AContent implements EE_IContent {

    public function __construct() {
        // первый раз определяем есть ли filehtml
        $filePHP = new ReflectionClass($this);
        $fileHTML = str_replace('.php', '.html', $filePHP->getFileName());
        if (file_exists($fileHTML)) {
            $this->_filehtml = $fileHTML;
        }

        parent::__construct();
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
        if (isset($this->_controlArray[$controlName])) {
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
        unset($this->_controlUnsetArray[$controlName]);

        $this->setValue('arg_'.$controlName, $controlValue);
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
        $this->_controlUnsetArray[$controlName] = true;
    }

    /**
     * Доступно ли значение control-value
     * true - доступно
     * false - явно стерто
     *
     * @param string $controlName
     *
     * @return bool
     */
    public function isControlValue($controlName) {
        if (isset($this->_controlUnsetArray[$controlName])) {
            return false;
        } else {
            return true;
        }
    }

    public function process() {

    }

    /**
     * Отрисовать контент (отрендерить в html-код).
     *
     * @return string
     */
    public function render() {
        parent::render();

        // если html-файла нет - то нет смысла продолжать
        $file = $this->getField('filehtml');
        if (!$file) {
            return '';
        }

        // получаем все параметры, которые надо передать в smarty
        $a = $this->getValueArray();

        $arguments = EE::Get()->getRequest()->getArgumentArray();
        foreach ($arguments as $name => $value) {
            if (is_array($value)) {
                continue;
            }
            $a['arg_'.$name] = $value;
            if ($this->isControlValue($name)) {
                $a['control_'.$name] = htmlspecialchars($value);
            }
        }

        // передаем все параметры еще раз, в виде массива
        $a['contentValueArray'] = $a;

        // @todo стоит ли события переносить в EE?

        // рендерим контент
        $event = Events::Get()->generateEvent('EE:content.render:before');
        $event->setContent($this);
        $event->setRenderHTML('');
        $event->notify();

        $html = EE_Smarty::Get()->fetch($file, $a);

        // генерируем событие afterRender
        $event = Events::Get()->generateEvent('EE:content.render:after');
        $event->setContent($this);
        $event->setRenderHTML($html);
        $event->notify();

        // достаем новый $html из события
        $html = $event->getRenderHTML();

        return $html;
    }

    public function clear() {
        parent::clear();

        $this->_controlArray = [];
        $this->_controlUnsetArray = [];

        // заполняем только одно поле - filehtml
        if ($this->_filehtml) {
            $this->setField('filehtml', $this->_filehtml);
        }
    }

    private $_controlArray = [];

    private $_controlUnsetArray = [];

    private $_filehtml = false;

}