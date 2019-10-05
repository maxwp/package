<?php
class index extends Engine_Class {

    public function process() {

        // ...
        if ($this->getControlValue('ok')) {
            $name = $this->getControlValue('name');
            $desc = $this->getControlValue('desc');
            $cb = $this->getControlValue('cb');
            // ...
        }
        // ...

    }

}