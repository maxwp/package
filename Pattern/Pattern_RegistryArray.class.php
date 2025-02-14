<?php
class Pattern_RegistryArray {

    /**
     * @throws Exception
     * @return mixed
     */
    public function get($key) {
        // это будет работать только в php8+
        return $this->_registryArray[$key] ?? throw new Exception("Key '{$key}' not found");
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
        return $this->_registryArray;
    }

    private array $_registryArray = [];

}