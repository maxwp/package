<?php
require __DIR__ . '/include.php';

$tsStart = microtime(true);

$routing = new EE_RoutingCLI();
$request = new EE_RequestCLI();
$response = new EE_ResponseCLI();

try {
    if ($request->getArgument('print', EE_IRequest::ARG_SOURCE_CLI)) {
        define('EE_PRINT', true);
    }
} catch (EE_Exception $e) {

}

EE::Get()->setRouting($routing);
EE::Get()->execute($request, $response);

$tsFinish = microtime(true);

$data = $response->getData();
if ($data) {
    print_r($data);
}

print "\n\n";
print "done:\n";
print "code     = ".$response->getCode()."\n";
print "start    = ".date('Y-m-d H:i:s', $tsStart)." ($tsStart)\n";
print "finish   = ".date('Y-m-d H:i:s', $tsFinish)." ($tsFinish)\n";
print "duration = ".($tsFinish - $tsStart)." sec.\n";
print "\n";