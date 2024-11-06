<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Clear some html-tags. HTML tags will be removed with tag and content.
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */
class TextProcessor_ActionHTMLTagsClear implements TextProcessor_IAction {

    public function __construct($tagsArray) {
        if (!$tagsArray || !is_array($tagsArray)) {
            throw new TextProcessor_Exception();
        }

        $this->_tagsArray = $tagsArray;
    }

    /**
     * @param string $text
     * @return string
     */
    public function process($text) {
        $p = new TextProcessor();

        foreach ($this->getTagsArray() as $tag) {
            $p->addAction(new TextProcessor_ActionPregReplace(
            "/<$tag(?:\s+)(?:.*?)>(.*?)<\/$tag>/uis",
            ''
            ));

            $p->addAction(new TextProcessor_ActionPregReplace(
            "/<$tag(?:\s*)>(.*?)<\/$tag>/uis",
            ''
            ));

            $p->addAction(new TextProcessor_ActionPregReplace(
            "/<$tag(?:\s+)(?:.*?)>/uis",
            ''
            ));

            $p->addAction(new TextProcessor_ActionPregReplace(
            "/<$tag(?:\s*)>/uis",
            ''
            ));
        }

        return $p->process($text);
    }

    /**
     * @return array
     */
    public function getTagsArray() {
        return $this->_tagsArray;
    }

    private $_tagsArray = array();

}