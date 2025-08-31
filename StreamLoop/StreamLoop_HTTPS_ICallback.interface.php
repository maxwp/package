<?php
interface StreamLoop_HTTPS_ICallback {

    public function onResponse(StreamLoop_HTTPS $handler, $tsRequest, $tsResponse, $statusCode, $statusMessage, $headerArray, $body);
    public function onError(StreamLoop_HTTPS $handler, $tsRequest, $tsResponse, $statusCode, $statusMessage);

    // @todo лучше DTO-объектами передавать request/response?
    // @todo отказаться везде от tsSelect, его передавать в response, а уже внутри call я могу мерять скорость парсинга

}