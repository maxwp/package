<?php
// сколько потоков Eventic'a запускать?
$threadCount = @$argv[1];
if (!$threadCount) {
    $threadCount = 10;
}

$dirpath = __DIR__;

// бесконечно запускаем потоки на flock'ах
// каждые 10 секунд
// на случай если какой-то worker Eventic'a вылетит
while (1) {
    for ($j = 1; $j <= $threadCount; $j++) {
        $path = "/usr/bin/flock -n $dirpath/pid/ee-worker-thread-$j.pid /usr/bin/php -f $dirpath/ee-worker-thread.php $j > /dev/null 2>&1 &";
        print $path."\n";
        exec($path);
    }

    sleep(10);
}