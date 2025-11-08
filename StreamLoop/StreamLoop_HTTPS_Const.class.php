<?php
final class StreamLoop_HTTPS_Const {
    public const STATE_CONNECTING = 1;
    public const STATE_HANDSHAKE = 2;
    public const STATE_WAIT_FOR_RESPONSE_HEADERS = 3;
    public const STATE_WAIT_FOR_RESPONSE_BODY = 4;
    public const STATE_READY = 5;

}