<?php
class Class1 {

    private $_x = 1;


    private function _test() {
        $x = $this->_x;

        for ($i = 0; $i < 100; $i++) {
            if ($x > $i) {

            }
        }
    }

    public function run() {
        while (1) {
            $t = microtime(true);

            for ($j = 0; $j < 1_000_000; $j++) {
                $this->_test();
            }

            $t = microtime(true) - $t;
            $t *= 1000;
            var_dump($t);
        }
    }

}

$object = new Class1();
$object->run();
