<?php
class StreamLoop {

    /**
     * Регистрация handler'a: в этот момент у меня уже должен быть stream & streamID,
     * иначе его нельзя зарегистрировать.
     *
     * @param StreamLoop_AHandler $handler
     * @return void
     */
    public function registerHandler(StreamLoop_AHandler $handler) {
        $this->_handlerArray[$handler->streamID] = $handler;
    }

    public function unregisterHandler($handler) {
        // @todo как менять handler?
        unset($this->_handlerArray[$handler->streamID]);
    }

    public function stop() {
        $this->_loopRunning = false;
    }

    public function run(StreamLoop_IRun $onRun) {
        if (!$this->_handlerArray) {
            throw new StreamLoop_Exception('No handler array');
        }

        $this->_loopRunning = true;

        // to locals
        $streamSelectTimeoutUS = $this->_streamSelectTimeoutUS;

        // event loop
        while (1) {
            // тут я не могу вынести в locals, потому что цикл могут остановить с наружи
            if (!$this->_loopRunning) {
                break;
            }

            $tsNow = microtime(true);

            $onRun->onRun($tsNow);

            // копирование массивов, в них уже задано что нужно для stream_select
            $r = $this->selectReadArray;
            $w = $this->selectWriteArray;
            $e = $this->selectExceptArray;
            $timeoutToArray = $this->selectTimeoutToArray;

            // если ничего нет - пауза на тот же тайм-аут
            if (!$r && !$w && !$e) {
                usleep($streamSelectTimeoutUS);
                continue;
            }

            // вот тут определить сколько us до ближайшего timeout'a
            // а также учитывать глобальный timeout loop'a
            $timeoutToArray[] = $tsNow + $streamSelectTimeoutUS / 1_000_000;
            $timeout = min($timeoutToArray) - $tsNow;
            if ($timeout <= 0) {
                $timeout = 0;
                // при timeout == 0 мне надо вызывать select потому что надо понять в каких потоках шо есть
            }

            $result = stream_select($r, $w, $e, 0, $timeout * 1_000_000);
            if ($result === false) {
                throw new StreamLoop_Exception('stream_select failed');
            }

            $calledArray = [];

            foreach ($r as $stream) {
                $id = (int) $stream;
                $this->_handlerArray[$id]->readyRead();
                $calledArray[$id] = true;
            }

            foreach ($w as $stream) {
                $id = (int) $stream;
                $this->_handlerArray[$id]->readyWrite();
                $calledArray[$id] = true;
            }

            foreach ($e as $stream) {
                $id = (int) $stream;
                $this->_handlerArray[$id]->readyExcept();
                $calledArray[$id] = true;
            }

            $tsEnd = microtime(true);

            // если для handler не вызывался сейчас ни один ready*
            // и при этом я перешел за timeout
            // = то надо вызвать readySelectTimeout
            foreach ($this->_handlerArray as $streamID => $handler) {
                $tto = $handler->timeoutTo; // to locals
                if ($tto > 0 && $tto <= $tsEnd) {
                    if (empty($calledArray[$streamID])) {
                        $handler->readySelectTimeout();
                    }
                }
            }
        }
    }

    public function setStreamSelectTimeout($us) {
        $this->_streamSelectTimeoutUS = $us;
    }

    private $_streamSelectTimeoutUS = 1_000_000;

    private $_loopRunning;
    /**
     * @var array<StreamLoop_AHandler>
     */
    private $_handlerArray = [];

    public $selectReadArray = [];
    public $selectWriteArray = [];
    public $selectExceptArray = [];
    public $selectTimeoutToArray = [];

}