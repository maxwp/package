<?php
interface EE_IRouting {

    // по IRequest мы определяем имя IProcessable-класса (контента), с которого будет запуск движка
    public function matchContent(EE_IRequest $request);

}