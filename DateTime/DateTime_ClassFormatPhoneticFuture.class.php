<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Фонетическое форматирование дат в будущем
 */
class DateTime_ClassFormatPhoneticFuture implements DateTime_IClassFormat {

    public function __construct($language = 'ru') {
        $this->_language = $language;
    }

    public function setFormat($format) {
        $this->_format = $format;
    }

    public function setDate($timestamp) {
        $this->_timestamp = $timestamp;
    }

    public function __toString() {
        $a = DateTime_Object::FromTimeStamp($this->_timestamp)->setFormat('l, d F Y, H:i');

        $a = str_replace('January', DateTime_Translate::Get()->getTranslateSecure('january'), $a);
        $a = str_replace('February', DateTime_Translate::Get()->getTranslateSecure('february'), $a);
        $a = str_replace('March', DateTime_Translate::Get()->getTranslateSecure('march'), $a);
        $a = str_replace('April', DateTime_Translate::Get()->getTranslateSecure('april'), $a);
        $a = str_replace('May', DateTime_Translate::Get()->getTranslateSecure('may'), $a);
        $a = str_replace('June', DateTime_Translate::Get()->getTranslateSecure('june'), $a);
        $a = str_replace('July', DateTime_Translate::Get()->getTranslateSecure('july'), $a);
        $a = str_replace('August', DateTime_Translate::Get()->getTranslateSecure('august'), $a);
        $a = str_replace('September', DateTime_Translate::Get()->getTranslateSecure('september'), $a);
        $a = str_replace('October', DateTime_Translate::Get()->getTranslateSecure('october'), $a);
        $a = str_replace('November', DateTime_Translate::Get()->getTranslateSecure('november'), $a);
        $a = str_replace('December', DateTime_Translate::Get()->getTranslateSecure('december'), $a);

        $a = str_replace('Monday', DateTime_Translate::Get()->getTranslateSecure('monday'), $a);
        $a = str_replace('Tuesday', DateTime_Translate::Get()->getTranslateSecure('tuesday'), $a);
        $a = str_replace('Wednesday', DateTime_Translate::Get()->getTranslateSecure('wednesday'), $a);
        $a = str_replace('Thursday', DateTime_Translate::Get()->getTranslateSecure('thursday'), $a);
        $a = str_replace('Friday', DateTime_Translate::Get()->getTranslateSecure('friday'), $a);
        $a = str_replace('Saturday', DateTime_Translate::Get()->getTranslateSecure('saturday'), $a);
        $a = str_replace('Sunday', DateTime_Translate::Get()->getTranslateSecure('sunday'), $a);

        return $a;
    }

    private $_format;

    private $_timestamp;

    private $_language;

}