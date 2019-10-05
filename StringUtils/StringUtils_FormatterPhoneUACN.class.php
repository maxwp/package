<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Форматирование номера телефона в соответствии с регионом
 * Украина (UA) > Чернигов (CN)
 *
 * @author Max
 * @copyright WebProduction
 * @package StringUtils
 * @subpackage FormatterPhone
 */
class StringUtils_FormatterPhoneUACN extends StringUtils_FormatterPhoneDefault {

    private $_mode = false;

    public function setMode($mode) {
    	$this->_mode = $mode;
    }

    public function getMode() {
    	return $this->_mode;
    }

    /**
     * Установить предпочтительно полный формат номера
     * (начиная с +38...)
     *
     */
    public function setModeFull() {
        $this->setMode('full');
    }

    /**
     * Установить короткий формат номера.
     * Для черниговских городских номеров отбрасывается +38 (0462(2))
     *
     */
    public function setModeShort() {
        $this->setMode('short');
    }

    /**
     * @param string $digits
     * @return string
     */
    public function format() {
        $x = parent::format();
        $x = str_replace('(046) 22', '(04622) ', $x);
        $x = str_replace('(046) 2', '(0462) ', $x);

        if ($this->getMode() == 'full') {
            $digits = $this->getDigits();

            if (strlen($digits) == 5) {
                return '+38 (04622) '.$x;
            }
            if (strlen($digits) == 6) {
                return '+38 (0462) '.$x;
            }
            if (strlen($digits) == 10) {
                return '+38 '.$x;
            }
            if (strlen($digits) == 11 && $digits{0} == '8') {
                return '+3'.$x;
            }
        }

        if ($this->getMode() == 'short') {
            $x = preg_replace("/^\+3\s*/uis", '', $x);
            $x = preg_replace("/^8\s*/uis", '', $x);
            $x = str_replace('(04622) ', '', $x);
            $x = str_replace('(0462) ', '', $x);
        }

        return $x;
    }

    public function formatFull() {
        $mode = $this->getMode();
        $this->setModeFull();
        $x = $this->format();
        $this->setMode($mode);
        return $x;
    }

    public function formatShort() {
        $mode = $this->getMode();
        $this->setModeShort();
        $x = $this->format();
        $this->setMode($mode);
        return $x;
    }

    /**
     * @param string $phone
     * @return StringUtils_FormatterPhoneUACN
     */
    public static function Create($phone) {
        return new self($phone);
    }

}