<?php
interface StreamLoop_HTTPS_ICallback {

    public function onResponse(StreamLoop_HTTPS $handler, $tsRequest, $tsResponse, $statusCode, $statusMessage, $headerArray, $body);
    public function onError(StreamLoop_HTTPS $handler, $tsRequest, $tsResponse, $statusCode, $statusMessage);

}