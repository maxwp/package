<?php
class StreamLoop {

    public function run() {
        if (!$this->_handlerArray) {
            throw new StreamLoop_Exception('No handler array');
        }

        $this->_loopRunning = true;

        // event loop
        while (1) {
            // тут я не могу вынести в locals, потому что цикл могут остановить с наружи
            if (!$this->_loopRunning) {
                break;
            }

            $tsNow = microtime(true);

            // копирование массивов, в них уже задано что нужно для stream_select
            $r = $this->_selectReadArray;
            $w = $this->_selectWriteArray;
            $e = $this->_selectExceptArray;
            $timeoutToArray = $this->_selectTimeoutToArray;

            // вот тут определить сколько us до ближайшего timeout'a
            if ($timeoutToArray) {
                $timeoutS = 0;
                $timeoutUS = (min($timeoutToArray) - $tsNow) * 1_000_000;
                if ($timeoutUS <= 0) {
                    $timeoutUS = 0;
                }
            } else {
                // timeout может быть null - если нет timeout array вообще - то все сокеты (стримы) будут ждать вечно
                $timeoutUS = null;
                $timeoutS = null;
            }

            // так как в stream_select надо всегда передавать rwe, а если у нас нет стримов - то эмуляция через usleep()
            if (!$r && !$w && !$e) {
                usleep($timeoutUS);
                $result = 0; // int
            } else {
                $result = stream_select($r, $w, $e, $timeoutS, $timeoutUS);
            }

            // меряем время select'a
            $tsSelect = microtime(true);

            $calledArray = [];

            foreach ($r as $stream) {
                $id = (int) $stream;
                $this->_handlerArray[$id]->readyRead($tsSelect);
                $calledArray[$id] = true;
            }

            // наличие if тут оправдано, потому что чаще массив пустой
            if ($w) {
                foreach ($w as $stream) {
                    $id = (int) $stream;
                    $this->_handlerArray[$id]->readyWrite($tsSelect);
                    $calledArray[$id] = true;
                }
            }

            // наличие if тут оправдано, потому что чаще массив пустой
            if ($e) {
                foreach ($e as $stream) {
                    $id = (int) $stream;
                    $this->_handlerArray[$id]->readyExcept($tsSelect);
                    $calledArray[$id] = true;
                }
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
            foreach ($this->_selectTimeoutToArray as $streamID => $timeoutTo) {
                if ($timeoutTo > 0 && $timeoutTo <= $tsEnd) {
                    if (empty($calledArray[$streamID])) {
                        $this->_handlerArray[$streamID]->readySelectTimeout($tsSelect);
                    }
                }
            }
        }
    }

    public function stop() {
        $this->_loopRunning = false;
    }

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
        $streamID = $handler->streamID;
        if ($streamID) {
            unset($this->_handlerArray[$streamID]);
            unset($this->_selectReadArray[$streamID]);
            unset($this->_selectWriteArray[$streamID]);
            unset($this->_selectExceptArray[$streamID]);
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
            $this->_selectTimeoutToArray[$handler->streamID] = $timeout;
        } else {
            unset($this->_selectTimeoutToArray[$handler->streamID]);
        }
    }

    private $_loopRunning;
    /**
     * @var array<StreamLoop_AHandler>
     */
    private $_handlerArray = [];

    private array $_selectReadArray = [];
    private array $_selectWriteArray = [];
    private array $_selectExceptArray = [];
    private $_selectTimeoutToArray = []; // @todo на кучу heap?

}