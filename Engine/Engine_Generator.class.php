<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2016 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Генератор Engine-контентов
 *
 * @author    Max
 * @copyright WebProduction
 * @package   Engine
 */
class Engine_Generator {

    public function process() {
        $contents = Engine::GetContentDataSource()->getData();

        foreach ($contents as $x) {
            if ($x['filephp']) {
                if (!file_exists($x['filephp'])) {
                    @mkdir(dirname($x['filephp']), 0755, true);

                    $sclassname = basename($x['filephp']);
                    $sclassname = str_replace('.php', '', $sclassname);
                    $sclassname = str_replace('.class', '', $sclassname);
                    $sclass = '<?php
class '.$sclassname.' extends '.Engine::Get()->getContentClass().' {

    public function process() {

    }

}';
                    file_put_contents($x['filephp'], $sclass, LOCK_EX);
                }
            }
            if ($x['filehtml']) {
                if (!file_exists($x['filehtml'])) {
                    @mkdir(dirname($x['filehtml']), 0755, true);
                    file_put_contents($x['filehtml'], '', LOCK_EX);
                }
            }

            if ($x['filecss']) {
                $cssArray = $x['filecss'];
                if (!is_array($cssArray)) {
                    $cssArray = array($cssArray);
                }
                foreach ($cssArray as $css) {
                    if (!file_exists($css)) {
                        @mkdir(dirname($css), 0755, true);
                        file_put_contents($css, '', LOCK_EX);
                    }
                }
            }

            if ($x['filejs']) {
                $jsArray = $x['filejs'];
                if (!is_array($jsArray)) {
                    $jsArray = array($jsArray);
                }
                foreach ($jsArray as $js) {
                    if (!file_exists($js)) {
                        @mkdir(dirname($js), 0755, true);
                        file_put_contents($js, '', LOCK_EX);
                    }
                }
            }
        }
    }

    /**
     * Получить генератор.
     *
     * @return Engine_Generator
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    private function __construct() {

    }

    private static $_Instance = null;

}