<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software; you cannot redistribute it and/or
 * modify it.
 */

/**
 * BBCode text processor
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */
class TextProcessor_BBCode extends TextProcessor {

    public function __construct() {
        $this->addAction(new TextProcessor_ActionBBCodeB());
        $this->addAction(new TextProcessor_ActionBBCodeU());
        $this->addAction(new TextProcessor_ActionBBCodeI());
        $this->addAction(new TextProcessor_ActionBBCodeS());
        $this->addAction(new TextProcessor_ActionBBCodeURL());
        $this->addAction(new TextProcessor_ActionBBCodeImg());
        $this->addAction(new TextProcessor_ActionBBCodeColor());
        $this->addAction(new TextProcessor_ActionBBCodeQuote());
        $this->addAction(new TextProcessor_ActionBBCodeYoutube());
        $this->addAction(new TextProcessor_ActionBBCodeRutube());
        $this->addAction(new TextProcessor_ActionBBCodeVimeo());
        $this->addAction(new TextProcessor_ActionBBCodeCode());
        $this->addAction(new TextProcessor_ActionBBCodeEOL());
    }

}