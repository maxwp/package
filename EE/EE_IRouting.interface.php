<?php
interface EE_IRouting {

    // @todo rename to matchURI
    // по IRequest мы определяем имя IProcessable-класса (контента), с которого будет запуск движка
    public function matchClassName(EE_IRequest $request);

}