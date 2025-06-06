<?php
interface Connection_Socket_IReceiver {

    public function onReceive($ts, $data, $fromAddress, $fromPort);

}