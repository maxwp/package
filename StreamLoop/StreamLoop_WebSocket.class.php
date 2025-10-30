<?php
/**
 * Важное отличие StreamLoop_WebSocket от Connection_WebSocket:
 * SL_WS вызывает selectTimeout только ради websocket-layer frame-ping-pong, он не вызывает его
 * ради пустых callback.
 * Если нужны пустые callback - то надо добавлять timer object внутрь StreamLoop.
 * Такой подход делает меньше вызовов selectTimeout и позволяет держать сильно больше WebSocket-handler-ов внутри одного StreamLoop,
 * но timer не будет синхронизирован с last event от websocket. Хотя он и в C_WS может быть не синхронизирован из-за app-layer & iframe-layer ping-pong.
 *
 * @deprecated use AWebSocket
 */
class StreamLoop_WebSocket extends StreamLoop_AWebSocket {

    public function setCallback(StreamLoop_WebSocket_ICallback $callback) {
        $this->_callback = $callback;
    }

    protected function _onReceive($tsSelect, $payload) {
        $this->_callback->onReceive($this, $tsSelect, $payload);
    }

    protected function _onError($tsSelect, $payload) {
        $this->_callback->onError($this, $tsSelect, $payload);
    }

    protected function _onReady($tsSelect) {
        $this->_callback->onReady($this, $tsSelect);
    }

    private StreamLoop_WebSocket_ICallback $_callback;

}