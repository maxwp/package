<?php
include __DIR__ . '/MemSockServer.class.php';
include __DIR__ . '/MemSockServerConnection.class.php';

// подключаем пакет движка
include(__DIR__ . '/package/Eventic/include.php');

// подключаем локальный конфиг
include(__DIR__ . '/eventic.config.php');

$server = new MemSockServer('0.0.0.0',8080 ); // Binds to determined IP
$server->hook("connect","connect_function"); // On connect does connect_function($server,$client,"");
$server->hook("disconnect","disconnect_function"); // On disconnect does disconnect_function($server,$client,"");
$server->hook("input","handle_input"); // When receiving input does handle_input($server,$client,$input);

print "mserver starting...\n\n";

$server->infinite_loop(); // starts the loop.

function connect_function(MemSockServer $server, MemSockServerConnection $connection) {
    print "connected #".$connection->server_clients_index." ".$connection->client_ip."\n";
}

function disconnect_function(MemSockServer $server, MemSockServerConnection $connection) {
    print "disconnected #".$connection->server_clients_index."\n";
}

function handle_input(MemSockServer $server, MemSockServerConnection $connection, $input) {
    $t = microtime(true);

    $ci = $connection->server_clients_index;

    var_dump($input);

    if (preg_match("/^GET (.+?) HTTP/ius", $input, $r)) {
        //print_r($r);

        // формируем запрос
        $host = @$_SERVER['HTTP_HOST'];
        $url = $r[1];

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

        // выдаем код ответа
        $responseCode = $eeResponse->getCode();

        // выдаем заголовки
        /*$headerArray = $eeResponse->getHeaderArray();
        foreach ($headerArray as $key => $value) {
            header("$key: $value");
        }*/

        // выдаем тело ответа
        $html = "HTTP/1.1 200 OK
Cache-Control: no-store, no-cache, must-revalidate
Connection: keep-alive
Content-Type: text/html; charset=utf-8
Date: ".date('r')."
Expires: Thu, 19 Nov 1981 08:52:00 GMT
Pragma: no-cache
Transfer-Encoding: Identity
X-Powered-By: Eventic

".$eeResponse->getBody()." <br><br><br><br>".(number_format(microtime(true) - $t, 8));

        $connection->write($html);
        $server->disconnect($ci);
    } else {
        $server->disconnect($ci);
    }
}
