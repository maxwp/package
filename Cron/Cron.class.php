<?php
/**
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package Package
 */
class Cron {

    public function run($className, $argumentArray = [], $uniquePID = false) {
        if (!is_subclass_of($className, EE_AContent::class)) {
            throw new Exception("Class $className does not extend EE_AContent");
        }

        $data = [];
        $data['classname'] = $className;
        $data['argumentArray'] = $argumentArray;
        $data['pid'] = $uniquePID;

        $result = $this->_getRedisLocal()->sAdd('cron', json_encode($data));

        print "Added to cron minute: $className (".json_encode($argumentArray).") ($result)\n";
    }

    public function process($dirpath) {
        $redisLocal = $this->_getRedisLocal();
        while ($file = $redisLocal->sPop('cron')) {
            $data = json_decode($file, true);

            $className = $data['classname'];
            $argumentArray = $data['argumentArray'];
            $pid = $data['pid'];

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

            // строим имя pid'a если его нет
            if (!$pid) {
                $pid = md5($className.$argumentString);
            }
            if (!substr_count($pid, '.pid')) {
                $pid .= '.pid';
            }

            $command = "ee-run.php $className $argumentString";

            $logString = "> /dev/null 2>&1 &";
            $path = "/usr/bin/flock -n $dirpath/pid/$pid /usr/bin/php $dirpath/$command $logString";
            print $path . "\n";
            exec($path);
        }
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

    /**
     * @return Cron
     */
    public static function Get() {
        if (!self::$_Instance) {
            $classname = __CLASS__;
            self::$_Instance = new $classname();
        }

        return self::$_Instance;
    }

    private static $_Instance;

}