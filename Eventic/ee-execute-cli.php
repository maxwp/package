<?php
require __DIR__ . '/include.php';

$routing = new EE_RoutingCLI();
$request = new EE_RequestCLI();
$response = new EE_ResponseCLI();

EE::Get()->setRouting($routing);
EE::Get()->execute($request, $response);

print_r($response->getData());

print "\n\n";
print "code = ".$response->getCode()."\n";
print "done.\n\n";