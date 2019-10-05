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