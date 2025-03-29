<?php
class Cli {

    public static function PrintSGRStart(...$args) {
        if (defined('EE_PRINT')) {
            print "\033[".implode(';', $args)."m";
        }
    }

    public static function PrintSGREnd() {
        if (defined('EE_PRINT')) {
            print "\033[".self::RESET."m";
        }
    }

    public static function Print($s) {
        if (defined('EE_PRINT')) {
            print (string) $s; // это нужно для типизации в string, потому что я могу передать объект типа DateTime_Object
        }
    }

    public static function Print_n($s = '') {
        if (defined('EE_PRINT')) {
            print "$s\n";
        }
    }

    public static function Print_break($symbol = '-', $length = 80, $separator = "\n") {
        if (defined('EE_PRINT') ) {
            print $separator;
            print str_repeat($symbol, $length);
            print $separator;
            print "\n";
        }
    }

    public static function Print_t($s = '') {
        if (defined('EE_PRINT')) {
            print "$s\t";
        }
    }

    public static function Print_r($a) {
        if (defined('EE_PRINT')) {
            print_r($a);
        }
    }

    public static function Print_e($callback) {
        if (defined('EE_PRINT')) {
            print $callback();
        }
    }

    public static function Print_f($s, $format, $eol = ' ') {
        if (defined('EE_PRINT')) {
            if (substr_count($format, '%')) {
                print sprintf($format, $s) . $eol;
            } else {
                print sprintf('%1$' . $format, $s) . $eol;
            }
        }
    }

    // -------------------------------------------------
    // Сброс всех атрибутов к значениям по умолчанию
    // -------------------------------------------------
    public const RESET = "0";

    // -------------------------------------------------
    //  Атрибуты шрифта — включение
    // -------------------------------------------------
    public const BOLD_ON         = "1";  // Жирный
    public const FAINT_ON        = "2";  // Блеклый (уменьшенная яркость)
    public const ITALIC_ON       = "3";  // Курсив (не всегда поддерживается)
    public const UNDERLINE_ON    = "4";  // Подчёркнутый
    public const BLINK_SLOW_ON   = "5";  // Мигание (медленное)
    public const BLINK_RAPID_ON  = "6";  // Мигание (быстрое) — редко поддерживается
    public const REVERSE_ON      = "7";  // Инверсия (фон ↔ текст)
    public const CONCEAL_ON      = "8";  // Скрытый (виден только при копировании)
    public const CROSSED_OUT_ON  = "9";  // Зачёркнутый

    // -------------------------------------------------
    //  Сброс отдельных атрибутов (выключение)
    // -------------------------------------------------
    // 10m по умолчанию = основной шрифт
    public const BOLD_OFF         = "22"; // Сброс жирного и блеклого
    public const ITALIC_OFF       = "23"; // Сброс курсива
    public const UNDERLINE_OFF    = "24"; // Сброс подчёркивания
    public const BLINK_OFF        = "25"; // Сброс мигания
    // 26m зарезервирован
    public const REVERSE_OFF      = "27"; // Сброс инверсии
    public const CONCEAL_OFF      = "28"; // Сброс скрытого
    public const CROSSED_OUT_OFF  = "29"; // Сброс зачёркнутого

    // -------------------------------------------------
    //  Альтернативные шрифты
    // -------------------------------------------------
    public const FONT_PRIMARY     = "10"; // Основной шрифт (сброс альтернативного)
    public const FONT_ALTERNATE_1 = "11";
    public const FONT_ALTERNATE_2 = "12";
    public const FONT_ALTERNATE_3 = "13";
    public const FONT_ALTERNATE_4 = "14";
    public const FONT_ALTERNATE_5 = "15";
    public const FONT_ALTERNATE_6 = "16";
    public const FONT_ALTERNATE_7 = "17";
    public const FONT_ALTERNATE_8 = "18";
    public const FONT_ALTERNATE_9 = "19";

    // -------------------------------------------------
    //  Цвет текста (FG) — стандартные
    // -------------------------------------------------
    public const FG_BLACK   = "30";
    public const FG_RED     = "31";
    public const FG_GREEN   = "32";
    public const FG_YELLOW  = "33";
    public const FG_BLUE    = "34";
    public const FG_MAGENTA = "35";
    public const FG_CYAN    = "36";
    public const FG_WHITE   = "37";
    public const FG_DEFAULT = "39"; // цвет по умолчанию

    // -------------------------------------------------
    //  Цвет фона (BG) — стандартные
    // -------------------------------------------------
    public const BG_BLACK   = "40";
    public const BG_RED     = "41";
    public const BG_GREEN   = "42";
    public const BG_YELLOW  = "43";
    public const BG_BLUE    = "44";
    public const BG_MAGENTA = "45";
    public const BG_CYAN    = "46";
    public const BG_WHITE   = "47";
    public const BG_DEFAULT = "49"; // фон по умолчанию

    // -------------------------------------------------
    //  Яркие (bright) цвета текста
    // -------------------------------------------------
    public const FG_BLACK_BRIGHT   = "90";
    public const FG_RED_BRIGHT     = "91";
    public const FG_GREEN_BRIGHT   = "92";
    public const FG_YELLOW_BRIGHT  = "93";
    public const FG_BLUE_BRIGHT    = "94";
    public const FG_MAGENTA_BRIGHT = "95";
    public const FG_CYAN_BRIGHT    = "96";
    public const FG_WHITE_BRIGHT   = "97";

    // -------------------------------------------------
    //  Яркие (bright) цвета фона
    // -------------------------------------------------
    public const BG_BLACK_BRIGHT   = "100";
    public const BG_RED_BRIGHT     = "101";
    public const BG_GREEN_BRIGHT   = "102";
    public const BG_YELLOW_BRIGHT  = "103";
    public const BG_BLUE_BRIGHT    = "104";
    public const BG_MAGENTA_BRIGHT = "105";
    public const BG_CYAN_BRIGHT    = "106";
    public const BG_WHITE_BRIGHT   = "107";

}