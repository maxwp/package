<?php
class StreamLoop {

    public function addHandler(StreamLoop_IHandler $handler) {
        $this->_handlerArray[] = $handler;
    }

    public function loopStop() {
        $this->_loopRunning = false;
    }

    public function loopRun(callable $callback) {
        if (!$this->_handlerArray) {
            throw new StreamLoop_Exception('No handler array');
        }

        $this->_loopRunning = true;

        // event loop
        while (1) {
            if ($this->_loopRunning === false) {
                break;
            }

            $callback();

            $r = [];
            $w = [];
            $e = [];
            $cnt = 0;
            $linkArray = [];

            foreach ($this->_handlerArray as $handler) {
                // handler будет выдавать stream только в том случае, если он что-то ждет
                // @todo возможно он сможет выдавать stream + что он ждет, но пока это не актуальная оптимизация
                $stream = $handler->getStream();
                if ($stream) {
                    $r[] = $stream;
                    $w[] = $stream;
                    $e[] = $stream;

                    $cnt ++;

                    $linkArray[(int)$stream] = $handler;
                }
            }

            // если ничего нет - пауза на тот же тайм-аут
            if ($cnt === 0) {
                usleep($this->_streamSelectTimeoutUS);
                continue;
            }

            $result = stream_select($r, $w, $e, $this->_streamSelectTimeoutUS);
            if ($result === false) {
                throw new StreamLoop_Exception("stream_select() failed");
            }

            foreach ($r as $stream) {
                $id = (int) $stream;
                $linkArray[$id]->readyRead();
            }

            foreach ($w as $stream) {
                $id = (int) $stream;
                $linkArray[$id]->readyWrite();
            }

            foreach ($e as $stream) {
                $id = (int) $stream;
                $linkArray[$id]->readyExcept();
            }
        }
    }

    public function setStreamSelectTimeout($us) {
        $this->_streamSelectTimeoutUS = $us;
    }

    /**
     * @var array<StreamLoop_IHandler>
     */
    private $_handlerArray = [];

    private $_streamSelectTimeoutUS = 500000;

    private bool $_loopRunning;

}