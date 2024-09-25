<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// подключаем пакет движка
include(__DIR__ . '/include.php');

// подключаем локальный конфиг
include(__DIR__ . '/../../eventic.config.php');

// подключаемся к redis
$redis = Connection::Get('redis')->getLink();

// формируем запрос
$url = @$_SERVER['REQUEST_URI'];
$host = @$_SERVER['HTTP_HOST'];
$request = array();
$request['url'] = $url;
$request['host'] = $host;
$request['get'] = $_GET;
$request['post'] = $_POST;
$request['files'] = $_FILES;
$request['cookie'] = $_COOKIE;
$request['timeout'] = 10;

$hash = md5(microtime(true).serialize($request));
$request['hash'] = $hash;

// создаем request
$eeRequest = new EE_Request($url, $host, $request['get'], $request['post'], $request['files'], $request['cookie']);

// дергаем движок
// получаем responce в обмен на request
$eeResponse = EE::Get()->execute($eeRequest);

// устанавливаем cookie
$cookieArray = $eeResponse->getCookieArray();
foreach ($cookieArray as $name => $data) {
    setcookie($name, $data['value'], $data['expires'], $data['path'], $data['domain'], $data['secure']);
}

// выдаем код ответа
$responseCode = $eeResponse->getCode();
if ($responseCode) {
    http_response_code($responseCode);
}

// выдаем заголовки
$headerArray = $eeResponse->getHeaderArray();
foreach ($headerArray as $key => $value) {
    header("$key: $value");
}

// выдаем тело ответа
print $eeResponse->getBody();
exit;