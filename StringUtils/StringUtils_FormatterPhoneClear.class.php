<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Форматирование номера телефона в чистые цифры
 *
 * @author Max
 * @author FreeFox
 * @copyright WebProduction
 * @package StringUtils
 * @subpackage FormatterPhone
 */
class StringUtils_FormatterPhoneClear extends StringUtils_AFormatter {

    private $_digits = '';

    public function __construct($phone) {
        parent::__construct($phone);

        $digits = preg_replace("/\D/", '', $phone);
        if ($phone[0] == '+'){
            // полный формат
            if (strlen($digits) != 12) {
                throw new StringUtils_Exception();
            }
        }
        if (strlen($digits) < 2 || strlen($digits) == 8 || strlen($digits) == 9) {
            throw new StringUtils_Exception();
        }

        $this->_digits = $digits;
    }

    public function format() {
        return $this->_digits;
    }

    public function getDigits() {
        return $this->_digits;
    }

    /**
     * @param string $phone
     * @return StringUtils_FormatterPhoneClear
     */
    public static function Create($phone) {
        return new self($phone);
    }

}