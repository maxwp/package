<?php
class Benchmark extends EE_AContentCli {

    public function process() {
        $className = $this->getArgument('class', EE_Typing::TYPE_STRING);
        $simulateCount = $this->getArgumentSecure('count', EE_Typing::TYPE_INT);
        if (!$simulateCount) {
            $simulateCount = 1_000_000;
        }

        $stub = new Benchmark_Stub();

        // сначала меряем пустой вызов и цикл
        $tAvg = [];
        for ($simulation = 1; $simulation <= 10; $simulation++) {
            $t = hrtime(true);

            for ($j = 1; $j <= $simulateCount; $j++) {
                $stub->process();
            }

            $t = hrtime(true) - $t;
            $tAvg[] = $t / $simulateCount;
        }
        $emptyCall = Array_Static::Avg($tAvg);

        // затем меряем реальный вызов

        /**
         * @var Benchmark_Interface
         */
        $testObject = new $className();

        $tAvg = [];
        for ($simulation = 1; $simulation <= 10; $simulation++) {
            $t = hrtime(true);

            for ($j = 1; $j <= $simulateCount; $j++) {
                $testObject->process();
            }

            $t = hrtime(true) - $t;
            $tx = $t / $simulateCount - $emptyCall;
            $this->print_n(round($tx).' ns/call');
            $tAvg[] = $tx;
        }

        $this->print_break();
        $this->print_n("empty       = ".round($emptyCall)." ns/call");
        $this->print_n("payload avg = ".round(Array_Static::Avg($tAvg))." ns/call");
        $this->print_n("payload med = ".round(Array_Static::Med($tAvg))." ns/call");
        $this->print_n("payload var = ".(Array_Static::Variance($tAvg)));
    }

}