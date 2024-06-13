<?php
class ee500 extends EE_AContent {

    public function process() {
        $error = EE::Get()->getResponse()->getData();

        $this->setValue('error', $error);
    }

}