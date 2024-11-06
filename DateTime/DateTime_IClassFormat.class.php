<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Интерфейс для форматтера дат
 */
interface DateTime_IClassFormat {

    public function setFormat($format);

    public function setDate($timestamp);

    public function __toString();

}