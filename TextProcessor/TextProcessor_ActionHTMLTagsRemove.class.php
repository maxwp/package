<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Some HTML tags will be removed, but content lost.
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */
class TextProcessor_ActionHTMLTagsRemove extends TextProcessor_ActionHTMLTagsClear {

    /**
     * @param string $text
     * @return string
     */
    public function process($text) {
        $p = new TextProcessor();

        foreach ($this->getTagsArray() as $tag) {
            $p->addAction(new TextProcessor_ActionPregReplace(
            "/<$tag(?:\s+)(?:.*?)>(.*?)<\/$tag>/uis",
            '$1'
            ));

            $p->addAction(new TextProcessor_ActionPregReplace(
            "/<$tag(?:\s*)>(.*?)<\/$tag>/uis",
            '$1'
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

}