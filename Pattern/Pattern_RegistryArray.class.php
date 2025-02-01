<?php
class Pattern_RegistryArray {

    public function register(string $key, $value): void {
        $this->_registryArray[$key] = $value;
    }

    /**
     * @throws Exception
     * @return mixed
     */
    public function get(string $key) {
        // @todo это будет работать только в php8+
        return $this->_registryArray[$key] ?? throw new Exception("Key '{$key}' not found");
    }

    private array $_registryArray = [];

}