<?php
// ВАЖНО: этот файл ничего не include-ит, чтобы все работало очень быстро
// никаких конфигов, чтобы все было очень очень быстро
// даже коннект к redis используется напрямую

$redis = new Redis();
$redis->pconnect('127.0.0.1', 6379);

// сколько
$timeout = 10;

$url = @$_SERVER['REQUEST_URI'];
$host = @$_SERVER['HTTP_HOST'];

$request = array();
$request['url'] = $url;
$request['host'] = $host;
$request['get'] = $_GET;
$request['post'] = $_POST;
$request['files'] = $_FILES;
$request['cookie'] = $_COOKIE;
$request['timeout'] = $timeout;

// хеш запроса, нужно чтобы был уникален
$hash = hash('murmur3f', microtime(true) + rand());
$request['hash'] = $hash;

// отправляем в redis запрос
$redis->lPush('eventic-request', json_encode($request));

$t = microtime(true);

if ($x = $redis->brPop('eventic-response-'.$hash, $timeout)) {
    $t = microtime(true) - $t;
    //var_dump($t);

    // получили ответ, разбираем его
    $responseArray = json_decode($x[1], true);

    // выдаем код ответа
    $responseCode = $responseArray['code'];
    if ($responseCode) {
        http_response_code($responseCode);
    }

    $cookieArray = $responseArray['cookieArray'];
    foreach ($cookieArray as $name => $data) {
        setcookie($name, $data['value'], $data['expires'], $data['path'], $data['domain'], $data['secure']);
    }

    $headerArray = $responseArray['headerArray'];
    foreach ($headerArray as $key => $value) {
        header("$key: $value");
    }

    print $responseArray['body'];
    exit;
}

// если ничего не выдало за timeout секунд - ошидка 500
// и пошло все нахер
http_response_code(500);
exit;