<?php
include(dirname(__FILE__) . '/package/ConnectionManager/include.php');

// параметры сесии
ini_set("session.cookie_domain", ".domain.com");
session_set_cookie_params(0, '/', '.domain.com');
@session_start();

// connection to database
Connection::Initialize(
    'mysql',
    new Connection_MySQLi(
        '127.0.0.1', // use 127.0.0.1, not localhost!
        'username',
        'password',
        'database'
    )
);

// connection to redis
Connection::Initialize(
    'redis',
    new Connection_Redis(
        '127.0.0.1',
        6379
    ),
    'redis'
);

// подключаем API
include(__DIR__ . '/api/include.php');

// всовываем роутинг в движок
// этот вызов находится в конфиге, потому что роутинг может быть разный в зависимости от проекта
EE::Get()->setRouting(EE_Routing::Get());
include(dirname(__FILE__) . '/eventic.routing.php');