<?php
interface StreamLoop_UDP_IReceiver {

    public function onReceive(StreamLoop_UDP $udp, $tsSelect, $ts, $message, $fromAddress, $fromPort);

}