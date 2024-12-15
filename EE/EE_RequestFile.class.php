<?php
class EE_RequestFile extends File {

    public function __construct($path, $name) {
        $name = trim($name);
        if (!$name) {
            throw new EE_Exception('Invalid file name');
        }

        parent::__construct($path);
        $this->_name = $name;
    }

    public function getName() {
        return $this->_name;
    }

    public function isUploaded() {
        return is_uploaded_file($this->_path);
    }

    private $_name;

}