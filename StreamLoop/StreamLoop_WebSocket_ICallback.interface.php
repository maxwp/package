<?php
interface StreamLoop_WebSocket_ICallback {

    public function onReceive(StreamLoop_WebSocket $handler, $tsSelect, $payload);
    public function onError(StreamLoop_WebSocket $handler, $tsSelect, $payload);

}