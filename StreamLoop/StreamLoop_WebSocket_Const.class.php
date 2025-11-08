<?php
final class StreamLoop_WebSocket_Const  {

    public const STATE_STOPPED = 0;
    public const STATE_CONNECTING = 1;
    public const STATE_HANDSHAKING = 2;
    public const STATE_UPGRADING = 3;
    public const STATE_READY = 4;
    public const ERROR_EOF = 1;
    public const ERROR_SSL = 2;
    public const ERROR_NO_PONG = 3;
    public const ERROR_HANDSHAKE = 4;
    public const ERROR_FRAME_CLOSED = 5;
    public const ERROR_USER = 6;
    public const ERROR_RESET_BY_PEER = 7;
    public const ERROR_TIMEOUT = 8;

}