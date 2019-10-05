<?php
class tpl_global extends Engine_Class {

    public function process() {
        $this->setValue('title', Engine::GetHTMLHead()->getTitle());
        $this->setValue('engine_includes', Engine::GetHTMLHead()->render());
    }

}