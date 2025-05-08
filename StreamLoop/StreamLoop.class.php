<?php
class StreamLoop {

    public function addHandler(StreamLoop_AHandler $handler) {
        $this->_handlerArray[] = $handler;
    }

    public function stop() {
        $this->_loopRunning = false;
    }

    public function run(callable $callback) {
        if (!$this->_handlerArray) {
            throw new StreamLoop_Exception('No handler array');
        }

        $this->_loopRunning = true;

        // event loop
        while (1) {
            if ($this->_loopRunning === false) {
                break;
            }

            $tsNow = microtime(true);

            $callback($tsNow);

            $r = [];
            $w = [];
            $e = [];
            $linkArray = [];

            $ok = false;
            foreach ($this->_handlerArray as $handler) {
                // всегда вызываю handler tick, если в handler указано что ему нужны тики
                // это упрощает логику на лишний call и бесконечное сравнение timestamp
                // важно: tick надо вызывать ДО чтения флагов RWE, потому что сам tick() может поменять эти флаги,
                // и может поменять сам resource stream
                if ($handler->flagTick) {
                    $handler->tick($tsNow);
                }

                $linkArray[(int)$handler->stream] = $handler; // @todo improve только если что-то поменялось

                // handler будет выдавать stream только в том случае, если он что-то ждет
                // и будет указыват что именно ждет этот stream
                if ($handler->flagRead) {
                    $r[] = $handler->stream;
                    $ok = true;
                }
                if ($handler->flagWrite) {
                    $w[] = $handler->stream;
                    $ok = true;
                }
                if ($handler->flagExcept) {
                    $e[] = $handler->stream;
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
     * @var array<StreamLoop_AHandler>
     */
    private $_handlerArray = [];

    private $_streamSelectTimeoutUS = 1_000_000;

    private bool $_loopRunning;

}