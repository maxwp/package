<?php
class Cron_Clear extends EE_AContentCli {

    public function process() {
        Cron::Get()->clear();
        exit;
    }

}