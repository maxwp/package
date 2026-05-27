<?php
class StreamLoop {

    // NB: некоторые проверки делаются только в debug-mode, так сделано специально чтобы hot-path получится
    // extremely fucking fast

    public function run() {
        // первый раз меряем tsSelect до круга
        $tsSelect = microtime(true);

        // event loop из сами залуп
        do {
            if ($this->_rweFlag) {
                $r = $this->_selectReadArray;
                $w = $this->_selectWriteArray; // @todo можно ли завернуть в if
                $e = $this->_selectExceptArray; // @todo а есть except?

                $timeoutUS = ($this->_selectTimeoutToMin - $tsSelect) * 1_000_000;
                if ($timeoutUS < 0) { // эта проверка нужна только потому, что нельзя отправлять negative timeoutUS
                    $timeoutUS = 0; // если <= 0 - значит какой-то таймаут уже близко, но все равно будет быстрая проверка флагов rwe
                }

                // stream_select() accepts values > 1000000 for microseconds and behaves correctly by normalizing internally.
                stream_select($r, $w, $e, 0, $timeoutUS);
            } else {
                // сюда пы попадаем если есть тайм-аут, но нет сокетов rwe
                // (копец редкая ситуация)

                // я специально обнуляю все до sleep:
                // sleep отдаст контекст и я не хочу тратиться на очистку переменных после пробуждения,
                // лучше сразу обрабатывать логику
                $r = []; // тут нужен array из-за foreach
                $w = false;
                $e = false;

                time_sleep_until($this->_selectTimeoutToMin);
            }

            // меряем время select'a сразу же
            $tsSelect = microtime(true);

            // 1. в 93% случаев я имею один элемент в r/w/e, и нет смысла делать to locals,
            //    а остальные проценты распределы примерно также: и математически не выгодно делать to locals trick.
            // 2. handlerArray to locals создает редкую проблему: если на readyRead я дропну handler, а потом на
            //    readyWrite попытаюсь шото сделать - нет элемента в массиве, а я не хочу обкладывать все isset-ами.
            // 3. так как я не делаю проверку result === false - то всегда надо быть готовым что readyXXX может вызваться
            //    в случае result === false, и тогда будут холостые обработчики. Но это было 1 раз за год.
            //    и было выгодно закосить эту проверку на result.

            // тут if не нужен, потому что чаще всего есть r
            foreach ($r as $streamID => $stream) {
                $this->_handlerArray[$streamID]->readyRead($tsSelect);
            }

            // наличие if тут оправдано, потому что чаще массив пустой
            if ($w) {
                foreach ($w as $streamID => $stream) {
                    $this->_handlerArray[$streamID]->readyWrite($tsSelect);
                }
            }

            // наличие if тут оправдано, потому что чаще массив пустой
            if ($e) {
                foreach ($e as $streamID => $stream) {
                    $this->_handlerArray[$streamID]->readyExcept($tsSelect);
                }
            }

            foreach ($this->_selectTimeoutToArray as $streamID => $timeoutTo) {
                if ($tsSelect >= $timeoutTo) {
                    $this->_handlerArray[$streamID]->readyTimeout($tsSelect);
                }
            }

            // только в режиме debug проверяем что с handler-ами
            # debug:start
            if (!$this->_handlerArray) {
                throw new StreamLoop_Exception('no handlers');
            }
            # debug:end

        } while (true);
    }

    public function updateHandler(StreamLoop_Handler_Abstract $handler, $flagRead, $flagWrite, $flagExcept, $timeoutTo) {
        // если streamID & что-то в RWET - регистрация, иначе снятие

        // to locals
        $streamID = $handler->streamID;
        $stream = $handler->stream;

        # debug:start
        if (!$streamID) {
            throw new StreamLoop_Exception('Cannot update handler without streamID');
        }
        # debug:end

        // нельзя ничего регистрировать с нулевым timeout:
        // я это проверяю только в debug-mode, чтобы в hot-path не тратить на это время
        # debug:start
        if (($flagRead || $flagWrite || $flagExcept)
            && $timeoutTo <= 0
        ) {
            throw new StreamLoop_Exception('Cannot register handler without timeout');
        }
        # debug:end

        $register = false; // @todo можно отказаться от этого флага

        if ($flagRead) {
            $this->_selectReadArray[$streamID] = $stream;
            $register = true;
        } else {
            unset($this->_selectReadArray[$streamID]);
        }

        if ($flagWrite) {
            $this->_selectWriteArray[$streamID] = $stream;
            $register = true;
        } else {
            unset($this->_selectWriteArray[$streamID]);
        }

        if ($flagExcept) {
            $this->_selectExceptArray[$streamID] = $stream;
            $register = true;
        } else {
            unset($this->_selectExceptArray[$streamID]);
        }

        if ($timeoutTo > 0) {
            $this->_selectTimeoutToArray[$streamID] = $timeoutTo;
            $register = true;
        } else {
            unset($this->_selectTimeoutToArray[$streamID]);
        }

        // регистрируем или снимаем
        if ($register) {
            $this->_handlerArray[$streamID] = $handler;
        } else {
            unset($this->_handlerArray[$streamID]);
        }

        // вычисляем min:
        // он должен быть обязательно, не может быть ситуации чтобы не было handler-ов которые ничего не ждут,
        // я тогда точно подвисну
        // @todo упростить на if: потому что я обычно продлеваю
        $this->_selectTimeoutToMin = min($this->_selectTimeoutToArray);

        // обновляем rwe флаг
        // хитрожопая if-tree optimization: чаще всего есть что-то в read и нет смысла делать OR-конструкцию
        // @todo встроить выше
        if ($this->_selectReadArray) {
            $this->_rweFlag = true;
        } elseif ($this->_selectWriteArray) {
            $this->_rweFlag = true;
        } elseif ($this->_selectExceptArray) {
            $this->_rweFlag = true;
        } else {
            $this->_rweFlag = false;
        }
    }

    /**
     * @var array<StreamLoop_Handler_Abstract>
     */
    private $_handlerArray = [];
    private $_rweFlag = false; // bool
    private $_selectReadArray = [];
    private $_selectWriteArray = [];
    private $_selectExceptArray = [];
    private $_selectTimeoutToArray = [];
    private $_selectTimeoutToMin = 0.0; // float

}