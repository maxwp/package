<?php
interface StreamLoop_WebSocket_IReceiver {

    public function onReceive(StreamLoop_WebSocket $websocket, $tsSelect, $ts, $payload);

}