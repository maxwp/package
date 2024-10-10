<?php
/**
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 * @copyright WebProduction
 * @package EE
 */
abstract class EE_AContentCli extends EE_AContent implements EE_IContent {

    public function print($s) {
        if (defined('EE_PRINT')) {
            print $s;
        }
    }

}