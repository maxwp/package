<?php
/**
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package Package
 */
class Cron {

    public function addToCron($command, $pidfile = false) {
        $redisLocal = new Redis();
        $redisLocal->connect('127.0.0.1');

        $a = [];
        $a['command'] = $command;
        $a['pidfile'] = $pidfile;

        $result = $redisLocal->sAdd('cron', json_encode($a));

        print "Added to cron: $command ($result)\n";
    }

    public function clearQue() {
        $redisLocal = new Redis();
        $redisLocal->connect('127.0.0.1');
        $redisLocal->del('cron');
    }

    public function processCron($dirpath) {
        $redisLocal = new Redis();
        $redisLocal->connect('127.0.0.1');

        // исполняем все на flock-ах
        while ($file = $redisLocal->sPop('cron')) {
            $data = json_decode($file, true);

            if ($data) {
                // new format
                $command = $data['command'];
                $pidfile = $data['pidfile'];
            } else {
                // old format
                // @todo когда-нибудь закосить :)
                $command = $file;
                $pidfile = false;
            }

            // строим имя pid'a если его нет
            if (!$pidfile) {
                $pidfile = $command;
                $pidfile = str_replace(' ', '_', $pidfile);
                $pidfile = str_replace('/', '_', $pidfile);
                $pidfile = str_replace('"', '_', $pidfile);
                $pidfile = str_replace('--', '_', $pidfile);
            }

            // .pid дописываем силой
            if (!substr_count($pidfile, '.pid')) {
                $pidfile = $pidfile.'.pid';
            }

            $logString = "> /dev/null 2>&1 &";
            $path = "/usr/bin/flock -n $dirpath/pid/$pidfile /usr/bin/php $dirpath/$command $logString";
            print $path . "\n";
            exec($path);
        }
    }

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