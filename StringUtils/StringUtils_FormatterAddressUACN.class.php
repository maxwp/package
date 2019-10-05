<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Форматирование адресов в соответствии с регионом
 * Украина (UA) > Чернигов (CN)
 *
 * @author Max
 * @copyright WebProduction
 * @package StringUtils
 * @subpackage FormatterAddress
 */
class StringUtils_FormatterAddressUACN extends StringUtils_AFormatter {

    private $_mode = false;

    public function setMode($mode) {
    	$this->_mode = $mode;
    }

    public function getMode() {
    	return $this->_mode;
    }

    /**
     * Установить предпочтительно полный формат
     */
    public function setModeFull() {
        $this->setMode('full');
    }

    /**
     * Установить предпочтительно короткий формат
     */
    public function setModeShort() {
        $this->setMode('short');
    }

    /**
     * @param string $digits
     * @return string
     */
    public function format() {
        $x = $this->getData();

        if ($this->getMode() == 'full') {
            // ул. превращаем улица
            $x = str_replace('ул.', 'улица', $x);
            // пр. превращаем проспект
            $x = str_replace('пр.', 'проспект', $x);
            // д. в дом
            $x = str_replace('д.', 'дом', $x);
            // кв. в квартира
            $x = str_replace('кв.', 'квартира', $x);
            // оф. в офис
            $x = str_replace('оф.', 'офис', $x);
            // добавляем Чернигов если его нет
            if (!preg_match('/Чернигов/uis', $x)) {
            	$x = 'г. Чернигов, '.$x;
            }
            // добавляем Украину
            if (!preg_match('/Украина/uis', $x)) {
            	$x = 'Украина, '.$x;
            }
        }

        if ($this->getMode() == 'short') {
            // убираем индекс (5 цифр)
            $x = preg_replace('/\d{5}/uis', '', $x);
            // убираем город Чернигов
            $x = preg_replace('/г\.\s*Чернигов/uis', '', $x);
            // и делаем обратные преобразования
            $x = str_replace('проспект', 'пр-т.', $x);
            $x = str_replace('улица', 'ул.', $x);
            $x = str_replace('офис', 'оф.', $x);
        }

        $x = str_replace("'", '', $x);
        $x = str_replace('"', '', $x);
        $x = str_replace(' , ', ', ', $x);
        $x = str_replace(' . ', '. ', $x);
        $x = str_replace(',,', ',', $x);
        $x = str_replace('..', '.', $x);
        $x = preg_replace('/^([\.\,]+)/uis', '', $x);
        $x = trim($x);

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
     * @param string $address
     * @return StringUtils_FormatterAddressUACN
     */
    public static function Create($address) {
        return new self($address);
    }

}