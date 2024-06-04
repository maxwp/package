<?php
$channel = $argv[1];
if (!$channel) {
    throw new EE_Exception("No channel argv[1]");
}

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// redis timeout setup
ini_set('default_socket_timeout', 24*3600);

$redisRequest = new Redis();
$redisRequest->connect('127.0.0.1', 6379);

$redisResponse = new Redis();
$redisResponse->connect('127.0.0.1', 6379);

// вечный цикл с паузами
// для обработки Eventic Request-ов из redis
while (1) {
    print 'EE remote worker on channel '.$channel."\n";

    $channelArray = [$channel];
    try {
        $redisRequest->subscribe($channelArray, function ($redis, $channel, $message) use ($redisResponse) {
            try {
                // засекаем время
                $t = microtime(true);

                $requestArray = json_decode($message, true);
                print_r($requestArray);

                $hash = $requestArray['hash'];
                $content = $requestArray['content'];
                $argumentArray = $requestArray['argumentArray'];

                $request = new EE_RequestRemote($content, $argumentArray);

                $routing = new EE_RoutingRemote();
                EE::Get()->setRouting($routing);

                $response = new EE_ResponseCLI();

                EE::Get()->execute($request, $response);

                $responseArray = [];
                $responseArray['hash'] = $hash;
                $responseArray['code'] = $response->getCode();
                $responseArray['data'] = $response->getData();
                $responseArray['ts_request'] = $requestArray['ts_request']; // время запроса
                $responseArray['ts_start'] = $t; // время начала обработки
                $responseArray['ts_response'] = microtime(true); // время впушивания ответа

                $redisResponse->lPush($hash, json_encode($responseArray));
                $redisResponse->expire($hash, $requestArray['timeout']); // чтобы не забивалась память

                print "Response code ".$response->getCode()."\n";

                // показываем все тайминги
                print "t(request > start) = ".($responseArray['ts_start'] - $responseArray['ts_request'])." sec.\n";
                print "t(ts_start > response) = ".($responseArray['ts_response'] - $responseArray['ts_start'])." sec.\n";
                print "t(request > response) = ".($responseArray['ts_response'] - $responseArray['ts_request'])." sec.\n";
                print "\n";
            } catch (Throwable $e) {
                print $e;

                // @todo в случае ошибки Eventic'a тоже надо записать ответ тоже
            }
        });
    } catch (Throwable $redisEx) {
        print_r($redisEx);
    }

    sleep(1);
}