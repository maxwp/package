<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Подсветка ссылок, nl2br, htmlspecialchars, подсветка email,
 * сворачивание цитат и тд.
 */
class TextProcessor_ActionTextToHTML implements TextProcessor_IAction {

    /**
     * Подсветка
     * 
     * @param string $text
     * 
     * @return string
     */
    public function process($content, $spaces = false, $mobile = false) {
        $content = trim($content);

        $content = str_replace("\t", '', $content);
        $content = str_replace("\r", '', $content);
        $content = preg_replace("/\n\s*\n\s*\n/", "\n\n", $content);

        $content = nl2br($content);

        if ($mobile) {
            $this->_limitSymbol = 40;
            $this->_mobile = true;
        } else {
            $this->_limitSymbol = 80;
        }

        $content = preg_replace_callback(
            "#(=\")?(\"?http://|https://|www.)[^<\s\n]+#",
            array($this, '_hightlightContentCallback'), 
            $content
        );
        $content = preg_replace_callback(
            "#(?<=\s|^)(\w+[.-]*\w+\@\w+[.-]*\w+\.\w+[.\w+]*)#",
            array($this, '_hightlightEmailCallback'), 
            $content
        );

        if (!$spaces) {
            $content = $this->_quoteFolding($content);
        }

        return $content;
    }

    private function _hightlightContentCallback($content) {
        if (substr_count($content[0], '">')) {
            return $content[0];
        }

        if ($content[1]) {
            return $content[0];
        }

        $url = $content[0];
        if (!substr_count($url, 'http://')
        && !substr_count($url, 'https://')
        && !substr_count($url, 'ftp://')
        && !substr_count($url, 'ftps://')
        ) {
            $url = 'http://'.$url;
        }

        $content[0] = StringUtils_Limiter::LimitLength($content[0], $this->_limitSymbol);

        //return '<a href="'.$url.'" target="_blank">'.$content[0].'</a>';
        if ($this->_mobile) {
            $url .='?open_in_browser=true';
        }
        return '<a href="'.$url.'">'.$content[0].'</a>';
    }
    
    private function _hightlightEmailCallback($content) {
        if (substr_count($content[0], '">')) {
            return $content[0];
        }

        $content[0] = StringUtils_Limiter::LimitLength($content[0], $this->_limitSymbol);

        return '<a href="mailto:'.$content[0].'">'.$content[0].'</a>';
    }

    private function _quoteFolding($text) {
        // issue #32569 - сворачивание цитат
        $a = explode("\n", $text);
        $quote = false;
        $text = '';
        foreach ($a as $line) {
            if (preg_match("/^>/", $line)) {
                if (!$quote) {
                    // стартуем сворачивание цитат
                    $quote = true;
                    $text .= '<blockquote>';
                }
            } elseif ($quote) {
                // завершаем сворачивание цитат
                $quote = false;
                $text .= '</blockquote>';
            }

            // текст как есть
            $text .= trim($line);
        }

        if ($quote) {
            // завершаем сворачивание цитат
            $quote = false;
            $text .= '</blockquote>';
        }

        return $text;
    }


    private $_limitSymbol = 80;
    private $_mobile = false;
}