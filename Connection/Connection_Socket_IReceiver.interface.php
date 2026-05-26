<?php
interface Connection_Socket_IReceiver {

    public function onReceive($tsReceived, $message, $fromAddress, $fromPort);

}