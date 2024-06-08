<?php
// 1. сначала положите в корень проекта файл eventic.config.php на основе example
// 2. направьте через nginx все запросы на файл index.php
// 3. в этом файле выберите выберите нужный способ подключения Eventic Engine
// 4. в crontab повесьте ee-worker.php <N> по примеру example.crontab, где <N> это количество потоков Eventic'a
//    смело вешайте 25 потоков на одно ядро, даже если это VCPU

// разовый запуск движка (удобно для отладки)
//include(__DIR__.'/package/EE/ee-execute-once.php');

// постоянный запуск движка на redis eventic
//include(__DIR__.'/package/EE/ee-execute-redis.php');