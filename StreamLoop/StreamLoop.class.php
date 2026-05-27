<?php
class StreamLoop {

    // @todo тут есть проблема, что если все handler'ы снимутся - будет вечный loop и это никак не остановить;
    //       но добавлять внутрь +1 проверку не охота;
    // @todo возможно лучше проверять на result === false & timeoutArray/updateHandler

    // @todo и еще стоит учест что timeout должен быть меньше 1_000_000, хотя почему-то это работает и с 10 sec

    public function run() {
        // первый раз меряем tsSelect до круга
        $tsSelect = microtime(true);

        // event loop из сами залуп
        do {
            $timeoutUS = ($this->_selectTimeoutToMin - $tsSelect) * 1_000_000;
            if ($timeoutUS < 0) {
                $timeoutUS = 0; // если <=0 - значит какой-то таймаут уже близко, но все равно будет проверка флагов rwe,
            }

            if ($this->_rweFlag) {
                $r = $this->_selectReadArray;
                $w = $this->_selectWriteArray;
                $e = $this->_selectExceptArray;
                
                $result = stream_select($r, $w, $e, 0, $timeoutUS);
            } else {
                // сюда пы попадаем если есть тайм-аут, но нет сокетов rwe

                // я специально обнуляю все до sleep:
                // sleep отдаст контекст и я не хочу тратиться на очистку переменных после пробуждения,
                // лучше сразу обрабатывать логику
                $r = []; // тут нужен array из-за foreach
                $w = false;
                $e = false;
                $result = 0;

                usleep($timeoutUS);
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

    public function updateHandler(StreamLoop_Handler_Abstract $handler, $flagRead, $flagWrite, $flagExcept, $timeoutTo) {
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

            # debug:start
            if ($register && $timeoutTo <= 0) {
                throw new StreamLoop_Exception('Cannot register handler without timeout');
            }
            # debug:end

            // регистрируем или снимаем
            if ($register) {
                $this->_handlerArray[$streamID] = $handler;
            } else {
                unset($this->_handlerArray[$streamID]);
            }

            // вычисляем min:
            // он должен быть обязательно, не может быть ситуации чтобы не было handler-ов которые ничего не ждут,
            // я тогда точно подвисну
            $this->_selectTimeoutToMin = min($this->_selectTimeoutToArray);

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