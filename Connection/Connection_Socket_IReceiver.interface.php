<?php
interface Connection_Socket_IReceiver {

    public function onReceive($ts, $message, $fromAddress, $fromPort);

}