<?php
require __DIR__ . '/include.php';

$routing = new EE_RoutingCLI();
$request = new EE_RequestCLI();
$response = new EE_ResponseCLI();

try {
    if ($request->getArgument('print', false, 'bool')) {
        define('EE_PRINT', true);
    }
} catch (EE_Exception $e) {

}

EE::Get()->setRouting($routing);
EE::Get()->execute($request, $response);

$data = $response->getData();
if ($data) {
    print_r($data);
}

print "\n\n";
print "done (code ".$response->getCode().")\n\n";