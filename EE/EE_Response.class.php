<?php
/**
 * Система ответа в Engine.
 * Позволяет удобно устанавливать ответы заголовки HTTP-ответа.
 * Например, настройки cache, gzip, last-modified, body, ...
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 * @copyright WebProduction
 * @package EE
 *
 * @todo rename to EE_ResponseHTTP
 */
class EE_Response implements EE_IResponse {

    public function getData() {
        return $this->_body;
    }

    public function setData($data) {
        $this->_body = $data;
    }

    public function setCode($code) {
        $this->_code = $code;
    }

    public function getCode() {
        return $this->_code;
    }

    public function setCookie($name, $value = "", $expires = 0, $path = "", $domain = "", $secure = true) {
        $this->_cookieArray[$name] = array(
            'value' => $value,
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
        );
    }

    public function getCookieArray() {
        return $this->_cookieArray;
    }

     /**
     * Задать заголовок языка
     *
     * @param string $language
     */
    public function setHeaderContentLanguage($language) {
        $this->setHeader('Content-Language', $language);
    }

    /**
     * Установить кеширование через Last-Modified
     *
     * @param int $seconds
     */
    public function setHeaderLastModifiedCaching($seconds) {
        $time = time() - $seconds;
        $this->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $time).' GMT');
    }

    /**
     * Задать mime-type ответа
     *
     * @param string $value
     */
    public function setHeaderContentType($value) {
        $this->setHeader('content-type', $value);
    }

    /**
     * Задать заголовок
     *
     * @param string $header
     * @param string $value
     */
    public function setHeader($header, $value = false) {
        $this->_headerArray[$header] = $value;
    }

    /**
     * Получить значение установленного заголовка
     *
     * @param string $header
     *
     * @return string
     */
    public function getHeader($header) {
        if (isset($this->_headerArray[$header])) {
            return $this->_headerArray[$header];
        }
        throw new EE_Exception("Header '{$header}' not found");
    }

    public function getHeaderArray() {
        return $this->_headerArray;
    }

    public function __construct() {
        // задаем идентификационный заголовок
        $this->setHeader('X-Powered-By', 'Eventic');

        // заголовок ставим в начало, чтобы в run контент мог его перетереть
        $this->setHeaderContentType('text/html; charset=utf-8');
    }

    private $_headerArray = [];

    private $_body = '';

    private $_cookieArray = [];

    private $_code;

}