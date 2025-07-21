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

    public function unregisterHandler(StreamLoop_AHandler $handler) {
        if ($handler->streamID) {
            unset($this->_handlerArray[$handler->streamID]);
        }
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
            $r = $this->_selectReadArray;
            $w = $this->_selectWriteArray;
            $e = $this->_selectExceptArray;
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
            // @todo ts ловить после select и передавать в readyXXX

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

            if (!$calledArray) {
                if ($result === false) {
                    throw new StreamLoop_Exception('stream_select failed');
                }
            }

            // заново узнаем время, потому что вызовы readyXXX могли занять время
            $tsEnd = microtime(true);

            // если для handler не вызывался сейчас ни один ready*
            // и при этом я перешел за timeout
            // = то надо вызвать readySelectTimeout
            foreach ($this->selectTimeoutToArray as $streamID => $timeoutTo) {
                if ($timeoutTo > 0 && $timeoutTo <= $tsEnd) {
                    if (empty($calledArray[$streamID])) {
                        $this->_handlerArray[$streamID]->readySelectTimeout();
                    }
                }
            }
        }
    }

    public function updateHandlerFlags(StreamLoop_AHandler $handler, $flagRead, $flagWrite, $flagExcept) {
        if (!$handler->streamID) {
            return;
        }

        // to locals
        $stream = $handler->stream;
        $streamID = $handler->streamID;

        // @todo менять только если что-то поменялось у меня?

        if ($flagRead) {
            $this->_selectReadArray[$streamID] = $stream;
        } else {
            unset($this->_selectReadArray[$streamID]);
        }

        if ($flagWrite) {
            $this->_selectWriteArray[$streamID] = $stream;
        } else {
            unset($this->_selectWriteArray[$streamID]);
        }

        if ($flagExcept) {
            $this->_selectExceptArray[$streamID] = $stream;
        } else {
            unset($this->_selectExceptArray[$streamID]);
        }
    }

    public function updateHandlerTimeout(StreamLoop_AHandler $handler, $timeout) {
        if ($timeout > 0) {
            $this->selectTimeoutToArray[$handler->streamID] = $timeout;
        } else {
            unset($this->selectTimeoutToArray[$handler->streamID]);
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

    private array $_selectReadArray = [];
    private array $_selectWriteArray = [];
    private array $_selectExceptArray = [];
    public $selectTimeoutToArray = [];

}