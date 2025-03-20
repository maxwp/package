<?php
// параметры php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// подключаем пакет движка
include(__DIR__ . '/include.php');

// подключаем локальный конфиг
include(__DIR__ . '/../../eventic.config.php');

// получаем соединение с redis
// через обертку ConnectionManager'a из-за опций и pconnect'a
$redis = Connection::GetRedis()->getLink();

// вечный цикл с паузами
// для обработки Eventic Request-ов из redis
while (1) {
    print 'connect EE worker'."\n";

    while ($x = $redis->brPop('eventic-request', 10*60)) {
        try {
            // засекаем время
            $t = microtime(true);

            $request = $x[1];
            $request = json_decode($request, true);
            print_r($request);

            $hash = $request['hash'];

            // создаем request
            // @todo создавать конированием
            $eeRequest = new EE_Request($request['url'], $request['host'], $request['get'], $request['post'], $request['files'], $request['cookie']);

            // @todo создавать конированием
            $eeResponse = new EE_ResponseHTTP();

            EE::Get()->execute($eeRequest, $eeResponse);

            $responseArray = [];
            $responseArray['hash'] = $hash;
            $responseArray['code'] = $eeResponse->getCode();
            $responseArray['cookieArray'] = $eeResponse->getCookieArray();
            $responseArray['headerArray'] = $eeResponse->getHeaderArray();
            $responseArray['body'] = $eeResponse->getData();

            $redis->lPush('eventic-response-'.$hash, json_encode($responseArray));
            $redis->expire('eventic-response-'.$hash, 5); // чтобы не забивалась память

            print "Response code ".$eeResponse->getCode()."\n";

            $t = microtime(true) - $t;
            print "round ts = $t\n";
        } catch (Exception $e) {
            print $e;
        }
    }

    sleep(1);
}