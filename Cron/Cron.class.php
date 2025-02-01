<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Cron supervisor
 */
class Cron extends Pattern_ASingleton {

    public function run($className, $argumentArray = [], $uniquePID = false, $logFile = false) {
        if (!is_subclass_of($className, EE_AContent::class)) {
            throw new Exception("Class $className does not extend EE_AContent");
        }

        $data = [];
        $data['classname'] = $className;
        $data['argumentArray'] = $argumentArray;
        $data['pid'] = $uniquePID;
        $data['logFile'] = $logFile;

        $result = $this->_getRedisLocal()->sAdd('cron', json_encode($data));

        $command = $this->_makeCommand($data);
        print "Added to cron: $command ($result)\n";
    }

    public function process($dirpath) {
        $redisLocal = $this->_getRedisLocal();
        while ($file = $redisLocal->sPop('cron')) {
            $data = json_decode($file, true);

            $pid = $data['pid'];
            $logFile = $data['logFile'];

            $command = $this->_makeCommand($data);

            // строим имя pid'a если его нет
            if (!$pid) {
                $pid = hash('fnv1a64', $command);
            }
            if (!substr_count($pid, '.pid')) {
                $pid .= '.pid';
            }

            if ($logFile) {
                $logString = ">> $dirpath/log/$logFile 2>&1 &";
            } else {
                $logString = "> /dev/null 2>&1 &";
            }

            $path = "/usr/bin/flock -n $dirpath/pid/$pid /usr/bin/php $dirpath/$command $logString";
            print "Run: ".$path . "\n";
            exec($path);
        }
    }

    private function _makeCommand($data) {
        $className = $data['classname'];
        $argumentArray = $data['argumentArray'];

        if ($argumentArray) {
            ksort($argumentArray);
        }
        $a = [];
        foreach ($argumentArray as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $a[] = "--$key=$v";
                }
            } else {
                $a[] = "--$key=$value";
            }
        }
        $argumentString = implode(' ', $a);
        unset($a);

        $command = "ee-run.php $className $argumentString";
        return $command;
    }

    /**
     * @return Redis
     */
    private function _getRedisLocal() {
        if ($this->_redis) {
            return $this->_redis;
        }

        $this->_redis = new Redis();
        $this->_redis->connect('127.0.0.1');
        return $this->_redis;
    }

    private $_redis;

    public function __construct() {

    }

}