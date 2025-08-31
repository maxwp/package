<?php
interface StreamLoop_UDP_ICallback {

    public function onReceive(StreamLoop_UDP $handler, $tsSelect, $ts, $message, $fromAddress, $fromPort);

    public function onError(StreamLoop_UDP $handler, $tsSelect, $errorCode);

}