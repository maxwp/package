<?php
class StreamLoop_HandlerWSS_StateMachine extends StateMachine {

    public const CONNECTING = 'connecting';
    public const HANDSHAKE = 'handshake';
    public const READY = 'ready';
    public const WAITING_FOR_UPGRADE = 'waiting-for-upgrade';
    public const WEBSOCKET_READY = 'websocket-ready';

    public function __construct() {
        $this->_registerTransition('start', self::CONNECTING);
        $this->_registerTransition(self::CONNECTING, self::HANDSHAKE);
        $this->_registerTransition(self::HANDSHAKE, self::READY);
        $this->_registerTransition(self::READY, self::WAITING_FOR_UPGRADE);
        $this->_registerTransition(self::WAITING_FOR_UPGRADE, self::WEBSOCKET_READY);

        $this->_setInitialState('start');
    }

}