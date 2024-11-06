<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
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