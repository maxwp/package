<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2012 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Получить отформатированый номер (с использованием пробелов).
 *
 * Примеры:
 * 02           -> 02
 * 102          -> 102
 * 5323         -> 53-23
 * 75323        -> 7-53-23
 * 275323       -> 27-53-23
 * 7275323      -> 727-53-23
 * 0637275323   -> (063) 7275323
 * 80637275323  -> 8 (063) 727-53-23 @todo: add +3
 * 380637275323 -> +38 (063) 727-53-23
 *
 * @package StringUtils
 * @subpackage FormatterPhone
 * @author FreeFox
 * @author Max
 * @copyright WebProduction
 */
class StringUtils_FormatterPhoneDefault extends StringUtils_FormatterPhoneClear {

    /**
     * @return string
     */
    public function format() {
        $digits = parent::format();

        switch (strlen($digits)) {
            case 2: case 3: return $digits;
            case 4: return substr($digits,0,2).'-'.substr($digits,2,2);
            case 5: return substr($digits,0,1).'-'.substr($digits,1,2).'-'.substr($digits,3,2);
            case 6: return substr($digits,0,2).'-'.substr($digits,2,2).'-'.substr($digits,4,2);
            case 7: return substr($digits,0,3).'-'.substr($digits,3,2).'-'.substr($digits,5,2);
            case 10: return '('.substr($digits,0,3).') '.substr($digits,3,3).'-'.substr($digits,6,2).'-'.substr($digits,8,2);
            case 11: return substr($digits,0,1).' ('.substr($digits,1,3).') '.substr($digits,4,3).'-'.substr($digits,7,2).'-'.substr($digits,9,2);
            case 12: return '+'.substr($digits,0,2).' ('.substr($digits,2,3).') '.substr($digits,5,3).'-'.substr($digits,8,2).'-'.substr($digits,10,2);
            default: return $digits;
        }
    }

    /**
     * Приведение номера к формату E.164 в Украине (+380...)
     *
     */
    public function formatE164UA()
    {
    	$digits = parent::format();
    	if (strlen($digits) < 9 || strlen($digits) > 12)
    	{
//    	   print strlen($digits);
    	   throw new StringUtils_Exception();
    	}
    	return '+'.substr('380', 0, 12-strlen($digits)).$digits;
    }

    /**
     * @param string $phone
     * @return StringUtils_FormatterPhoneDefault
     */
    public static function Create($phone) {
        return new self($phone);
    }

}