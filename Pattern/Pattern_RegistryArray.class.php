<?php
class Pattern_RegistryArray {

    /**
     * @throws Exception
     * @return mixed
     */
    public function get($key) {
        // это будет работать только в php8+
        return $this->_registryArray[$key] ?? throw new $this->_exceptionClass("Key '{$key}' not found");
    }

    public function set($key, $value) {
        $this->_registryArray[$key] = $value;
    }

    public function remove($key) {
        unset($this->_registryArray[$key]);
    }

    public function has($key) {
        return isset($this->_registryArray[$key]);
    }

    public function clean() {
        $this->_registryArray = [];
    }

    public function getArray() {
        // @todo заменить на public?
        return $this->_registryArray;
    }

    public function setExceptionClass(string $className) {
        $this->_exceptionClass = $className;
    }

    private array $_registryArray = [];

    private string $_exceptionClass = Pattern_Exception::class;

}