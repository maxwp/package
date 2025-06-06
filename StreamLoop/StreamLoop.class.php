<?php
class StreamLoop {

    public function addHandler(StreamLoop_AHandler $handler) {
        $this->_handlerArray[] = $handler;
    }

    public function stop() {
        $this->_loopRunning = false;
    }

    public function run(StreamLoop_IRun $onRun) {
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

            $onRun->onRun($tsNow);

            // @todo malloc fix

            $r = [];
            $w = [];
            $e = [];
            $linkArray = [];
            $timeoutToArray = [];

            $ok = false;
            foreach ($this->_handlerArray as $handler) {
                $streamID = (int)$handler->stream;
                if (!$streamID) {
                    continue;
                }
                $linkArray[$streamID] = $handler; // @todo improve только если что-то поменялось

                // вот тут у handler я могу спросить до какого времени ты хочешь timeout
                // он может вернуть 0, то есть ему насрать и он не хочет таймаут
                if ($handler->timeoutTo > 0) {
                    $timeoutToArray[] = $handler->timeoutTo;
                }

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

            // вот тут определить сколько us до ближайшего timeout'a
            // а также учитывать глобальный timeout loop'a
            $timeoutToArray[] = $tsNow + $this->_streamSelectTimeoutUS / 1_000_000;
            $timeout = min($timeoutToArray) - $tsNow;
            if ($timeout <= 0) {
                $timeout = 0;
            }

            $result = stream_select($r, $w, $e, 0, $timeout * 1_000_000);
            if ($result === false) {
                throw new StreamLoop_Exception("stream_select failed");
            }

            $callArray = [];

            foreach ($r as $stream) {
                $id = (int) $stream;
                $linkArray[$id]->readyRead();
                $callArray[$id] = true;
            }

            foreach ($w as $stream) {
                $id = (int) $stream;
                $linkArray[$id]->readyWrite();
                $callArray[$id] = true;
            }

            foreach ($e as $stream) {
                $id = (int) $stream;
                $linkArray[$id]->readyExcept();
                $callArray[$id] = true;
            }

            $tsEnd = microtime(true);

            // если для потока не вызывался сейчас ни один ready
            // и при этом я перешел за timeout
            // = то надо вызвать readySelectTimeout
            foreach ($linkArray as $streamD => $handler) {
                if ($handler->timeoutTo > 0
                    && empty($callArray[$streamD])
                    && $handler->timeoutTo <= $tsEnd
                ) {
                    $handler->readySelectTimeout();
                }
            }
        }
    }

    public function setStreamSelectTimeout($us) {
        $this->_streamSelectTimeoutUS = $us;
    }

    /**
     * @var array<StreamLoop_AHandler>
     */
    private $_handlerArray = []; // @todo registry?

    private $_streamSelectTimeoutUS = 1_000_000;

    private $_loopRunning;

}