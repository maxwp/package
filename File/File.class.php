<?php
class File {

    public function __construct($path) {
        if (substr_count($path, '://')) {
            throw new File_Exception("Invalid file path $path");
        }
        $this->_path = $path;
    }

    public function isExists() {
        return file_exists($this->_path);
    }

    public function getSize() {
        return filesize($this->_path);
    }

    public function isReadable() {
        return is_readable($this->_path);
    }

    public function read() {
        return file_get_contents($this->_path);
    }

    public function write($data) {
        return file_put_contents($this->_path, $data, LOCK_EX);
    }

    public function getMimeType() {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $this->_path);
        finfo_close($finfo);
        return $mimeType;
    }

    private $_path;

}