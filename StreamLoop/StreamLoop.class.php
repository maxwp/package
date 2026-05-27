<?php
class StreamLoop {

    public function run() {
        // первый раз меряем tsSelect до круга
        $tsSelect = microtime(true);

        // event loop из сами залуп
        do {
            // @todo что изменится если я заставлю каждый handler иметь тайм-аут принудительно
            // вот тут определить сколько us до ближайшего timeout'a
            if ($this->_selectTimeoutToMin) { // @todo у меня всегда ж есть timeout?
                $timeoutS = 0;
                $timeoutUS = ($this->_selectTimeoutToMin - $tsSelect) * 1_000_000;
                if ($timeoutUS <= 0) {
                    $timeoutUS = 0; // если <=0 - то это просто разовая проверка флагов rwe, значит какой-то таймаут уже близко
                }
            } else {
                // timeout может быть null - если нет timeout array вообще - то все сокеты (стримы) будут ждать вечно
                $timeoutS = null;
                $timeoutUS = null;
            }

            if ($this->_rweFlag) {
                $r = $this->_selectReadArray;
                $w = $this->_selectWriteArray;
                $e = $this->_selectExceptArray;

                $result = stream_select($r, $w, $e, $timeoutS, $timeoutUS);
            } elseif ($timeoutUS !== null) {
                // сюда пы попадаем только из-за timeout'a:

                // я специально обнуляю все до sleep (потому что sleep отдаст контекст и я не хочу тратиться на очистку переменных после пробуждения)
                $r = []; // тут нужен array из-за foreach
                $w = false;
                $e = false;
                $result = 0;

                usleep($timeoutUS);
            } else {
                // тут может быть странная ситуация, что нет rwe, а timeout is null - то это явно бажина,
                // потому что таймер должен был снять регистрацию handler'a
                throw new StreamLoop_Exception('No RWE and timeout is null');
            }

            // меряем время select'a сразу же
            $tsSelect = microtime(true);

            // тут я решил НЕ делать handlerArray to locals:
            // 1. в 93% случаев я имею один элемент в r/w/e, и нет смысла делать to locals,
            //    а остальные проценты распределы примерно также: и математически не выгодно делать to locals trick.
            // 2. handlerArray to locals создает редкую проблему: если на readyRead я дропну handler, а потом на
            //    readyWrite попытаюсь шото сделать - нет элемента в массиве, а я не хочу обкладывать все isset-ами.

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

            // ВАЖНО: при stream_select=false мы всё равно один раз обработаем r/w/e,
            // потому что ложный ready* для нас безопасен.
            // После этого loop упадет через do-while.
        } while ($result !== false);

        throw new StreamLoop_Exception('stream_select failed');
    }

    public function updateHandler(StreamLoop_Handler_Abstract $handler, $flagRead, $flagWrite, $flagExcept, $timeoutTo = false) {
        // если streamID & что-то в RWET - регистрация, иначе снятие

        $streamID = $handler->streamID;
        if ($streamID) {
            // to locals
            $stream = $handler->stream;

            $register = false;

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

            // вычисляем min
            // @todo что делать если handler-оа просто нет?
            if ($this->_selectTimeoutToArray) {
                $this->_selectTimeoutToMin = min($this->_selectTimeoutToArray);
            } else {
                $this->_selectTimeoutToMin = 0; // int-сравнение будет быстрее
            }

            // обновляем rwe флаг
            // хитрожопая if-tree optimization: чаще всего есть что-то в read и нет смысла делать OR-конструкцию
            // @todo встроить выше
            // @todo RWET flag?
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