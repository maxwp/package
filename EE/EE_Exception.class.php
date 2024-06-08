<?php
/**
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 * @copyright WebProduction
 * @package EE
 */
class EE_Exception extends Exception {

    public function __construct($message, $code = 0) {
        parent::__construct('EE: '.$message, $code);
    }

}
