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
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package TextProcessor
 */
class TextProcessor_ActionHTMLClear implements TextProcessor_IAction {

	/**
     * @param string $text
     * @return string
     */
	public function process($text) {
        $p = new TextProcessor();

        $p->addAction(new TextProcessor_ActionCSSClear());

        $p->addAction(new TextProcessor_ActionPregReplace(
        '/BGCOLOR="(.*?)"/uis',
        ''
        ));

        $p->addAction(new TextProcessor_ActionHTMLTagsRemove(
        array('a', 'b', 'u', 'i', 'font', 'div', 'form')
        ));

        $p->addAction(new TextProcessor_ActionHTMLTagsClear(
        array('input', 'textarea', 'button', 'style', 'script')
        ));

        $p->addAction(new TextProcessor_ActionPregReplace(
        '/&nbsp;/uis',
        ''
        ));

        return $p->process($text);
	}

}