<?php
require __DIR__.'/include.php';

$className = @$argv[1];

$object = new $className;
$object->main();

print "\n\ndone.\n\n";