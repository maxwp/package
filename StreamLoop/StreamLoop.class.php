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
            $linkArray = [];

            $ok = false;
            foreach ($this->_handlerArray as $handler) {
                // handler будет выдавать stream только в том случае, если он что-то ждет
                // и будет указыват что именно ждет этот stream
                $x = $handler->getStreamConfig();
                //print_r($x);
                $stream = $x[0];
                $linkArray[(int)$stream] = $handler;
                if ($x[1]) {
                    $r[] = $stream;
                    $ok = true;
                }
                if ($x[2]) {
                    $w[] = $stream;
                    $ok = true;
                }
                if ($x[3]) {
                    $e[] = $stream;
                    $ok = true;
                }
            }

            // если ничего нет - пауза на тот же тайм-аут
            if (!$ok) {
                usleep($this->_streamSelectTimeoutUS);
                continue;
            }

            $result = stream_select($r, $w, $e, 0, $this->_streamSelectTimeoutUS);
            if ($result === false) {
                throw new StreamLoop_Exception("stream_select failed");
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

    private $_streamSelectTimeoutUS = 500_000*2;

    private bool $_loopRunning;

}