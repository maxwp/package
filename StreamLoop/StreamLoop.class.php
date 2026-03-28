<?php
class StreamLoop {

    public function run() {
        if (!$this->_handlerArray) {
            throw new StreamLoop_Exception('No handler array');
        }

        // первый раз меряем tsSelect до круга
        $tsSelect = microtime(true);

        // event loop
        while (1) {
            // копирование массивов, в них уже задано что нужно для stream_select
            $r = $this->_selectReadArray;
            $w = $this->_selectWriteArray;
            $e = $this->_selectExceptArray;

            // вот тут определить сколько us до ближайшего timeout'a
            $timeoutMin = $this->_selectTimeoutToMin; // нельзя переносить внутрь if'a, будет +1 ns
            if ($timeoutMin) {
                $timeoutS = 0;
                $timeoutUS = ($timeoutMin - $tsSelect) * 1_000_000;
                if ($timeoutUS <= 0) {
                    $timeoutUS = 0; // если <=0 - то это просто разовая проверка флагов rwe, значит какой-то таймаут уже близко
                }
            } else {
                // timeout может быть null - если нет timeout array вообще - то все сокеты (стримы) будут ждать вечно
                $timeoutS = null;
                $timeoutUS = null;
            }

            if ($this->_rweFlag) {
                $result = stream_select($r, $w, $e, $timeoutS, $timeoutUS);
            } elseif ($timeoutUS !== null) {
                usleep($timeoutUS);
                $result = 0;
            } else {
                // тут может быть странная ситуация, что нет rwe, а timeout is null - то это явно бажина,
                // потому что таймер должен был снять регистрацию handler'a
                throw new StreamLoop_Exception('No RWE and timeout is null');
            }

            // меряем время select'a
            $tsSelect = microtime(true);

            $handlerArray = $this->_handlerArray; // to locals
            $timeoutArray = $this->_selectTimeoutToArray;

            foreach ($r as $streamID => $stream) {
                $handlerArray[$streamID]->readyRead($tsSelect);
                unset($timeoutArray[$streamID]);
            }

            // наличие if тут оправдано, потому что чаще массив пустой
            if ($w) {
                foreach ($w as $streamID => $stream) {
                    $handlerArray[$streamID]->readyWrite($tsSelect);
                    unset($timeoutArray[$streamID]);
                }
            }

            // наличие if тут оправдано, потому что чаще массив пустой
            if ($e) {
                foreach ($e as $streamID => $stream) {
                    $handlerArray[$streamID]->readyExcept($tsSelect);
                    unset($timeoutArray[$streamID]);
                }
            }

            // если для handler не вызывался сейчас ни один ready*
            // и при этом я перешел за timeout
            // = то надо вызвать readySelectTimeout
            if ($timeoutArray) {
                foreach ($timeoutArray as $streamID => $timeoutTo) {
                    if ($timeoutTo > 0 && $timeoutTo <= $tsSelect) {
                        $handlerArray[$streamID]->readySelectTimeout($tsSelect);
                    }
                }
            }

            // эта проверка должна быть в самом конце: она срабатывает редко, и если ее поставить сразу после select
            // то будет бесполезная задержка на проверку if===false перед вызовом логики, которая в 99.99999% не оправдана
            if ($result === false) {
                throw new StreamLoop_Exception('stream_select failed');
            }
        }
    }

    /**
     * Регистрация handler'a: в этот момент у меня уже должен быть stream & streamID,
     * иначе его нельзя зарегистрировать.
     *
     * @param StreamLoop_Handler_Abstract $handler
     * @return void
     */
    public function registerHandler(StreamLoop_Handler_Abstract $handler) {
        // @todo тут есть прикол что регистрация handler'a заранее не нужна, как и его снятие.
        // @todo достаточно оперировать только updateFlags и если что-то есть, то регистрировать handler на лету

        // проверка чтобы я не натупил и не вызвал дублей
        if (isset($this->_handlerArray[$handler->streamID])) {
            throw new StreamLoop_Exception('stream_handler already registered');
        }

        $this->_handlerArray[$handler->streamID] = $handler;
    }

    public function unregisterHandler(StreamLoop_Handler_Abstract $handler) {
        // важно: этот метод надо вызывать ДО fclose, пока есть streamID
        $streamID = $handler->streamID;
        if ($streamID) {
            unset(
                $this->_handlerArray[$streamID],
                $this->_selectReadArray[$streamID],
                $this->_selectWriteArray[$streamID],
                $this->_selectExceptArray[$streamID],
                $this->_selectTimeoutToArray[$streamID]
            );
        }

        // обновляем rwe флаг
        // хитрожопая if-tree optimization: чаще всего есть что-то в read и нет смысла делать OR-конструкцию
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

    public function updateHandlerFlags(StreamLoop_Handler_Abstract $handler, $flagRead, $flagWrite, $flagExcept) {
        if (!$handler->streamID) {
            return;
        }

        // to locals
        $stream = $handler->stream;
        $streamID = $handler->streamID;

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

        // обновляем rwe флаг
        // хитрожопая if-tree optimization: чаще всего есть что-то в read и нет смысла делать OR-конструкцию
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
     * Важно: таймер может сработать не супер точно, а с дрейфом на время обработки handler-ов.
     * Это связано с тем, что я использую prev_tsSelect для расчета таймеров следуюего круга.
     * Потому что запрос времени занимает 40 ns, и это реально 1/3 от всего event loop'a.
     *
     * @param StreamLoop_Handler_Abstract $handler
     * @param $timeoutTo
     * @return void
     */
    public function updateHandlerTimeoutTo(StreamLoop_Handler_Abstract $handler, $timeoutTo) {
        // @todo слепить с updateHandlerFlags и переименовать в updateHandler(),
        // @todo плюс updateHandler сам снимает регистрацию если что
        // @todo upd: надо сделать updateHandler, updateHandlerFlags, updateHandlerTimeout(), потому что везде чуть разная логика и где-то менять флаги не надо.
        // хотя насколько дороже вызов только timeout чем со всеми флагами?

        if ($timeoutTo > 0) {
            $this->_selectTimeoutToArray[$handler->streamID] = $timeoutTo;
        } else {
            unset($this->_selectTimeoutToArray[$handler->streamID]);
        }

        // вычисляем min
        // @todo тут можно трошки подкрутить чтобы не вызывать min()
        if ($this->_selectTimeoutToArray) {
            $this->_selectTimeoutToMin = min($this->_selectTimeoutToArray);
        } else {
            $this->_selectTimeoutToMin = 0; // int-сравнение будет быстрее
        }
    }

    /**
     * @var array<StreamLoop_Handler_Abstract>
     */
    private $_handlerArray = [];
    private $_rweFlag = false; // bool
    private array $_selectReadArray = [];
    private array $_selectWriteArray = [];
    private array $_selectExceptArray = [];
    private array $_selectTimeoutToArray = [];
    private $_selectTimeoutToMin = 0.0;

}