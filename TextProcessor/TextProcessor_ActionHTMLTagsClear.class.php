<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2011 WebProduction <webproduction.com.ua>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU LESSER GENERAL PUBLIC LICENSE
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
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