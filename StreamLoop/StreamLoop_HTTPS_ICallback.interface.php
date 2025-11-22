<?php
/**
 * @deprecated
 */
interface StreamLoop_HTTPS_ICallback {

    public function onResponse(StreamLoop_HTTPS $handler, $tsSelect, $tsRequest, $statusCode, $statusMessage, $headerArray, $body);
    public function onError(StreamLoop_HTTPS $handler, $tsRequest, $tsResponse, $statusCode, $statusMessage);

}