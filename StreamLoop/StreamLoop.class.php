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

            $r = [];
            $w = [];
            $e = [];
            $linkArray = [];
            $timeoutToArray = [];

            $ok = false;
            foreach ($this->_handlerArray as $handler) {
                $streamID = $handler->streamID;
                if (!$streamID) {
                    continue;
                }
                $linkArray[$streamID] = $handler; // @todo improve только если что-то поменялось

                // вот тут у handler'a я могу спросить до какого времени ты хочешь timeout
                // он может вернуть 0, то есть ему насрать и он не хочет таймаут
                $timeoutTo = $handler->timeoutTo; // to locals
                if ($timeoutTo > 0) {
                    $timeoutToArray[] = $timeoutTo;
                }

                // handler будет выдавать stream только в том случае, если он что-то ждет
                // и будет указыват что именно ждет этот stream
                $stream = $handler->stream;
                if ($handler->flagRead) {
                    $r[] = $stream;
                    $ok = true;
                }
                if ($handler->flagWrite) {
                    $w[] = $stream;
                    $ok = true;
                }
                if ($handler->flagExcept) {
                    $e[] = $stream;
                    $ok = true;
                }
            }

            // если ничего нет - пауза на тот же тайм-аут
            if (!$ok) {
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

            // если для потока не вызывался сейчас ни один ready*
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
    private $_handlerArray = [];

    private $_streamSelectTimeoutUS = 1_000_000;

    private $_loopRunning;

}