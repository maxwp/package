<?php
/**
 * Get content-data from URL with cache TTL.
 *
 * @author    Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package   TextProcessor
 */
class TextProcessor_ActionContentFromURL implements TextProcessor_IAction {

    /**
     * Создать текстовый процессор
     *
     * @param string $url URL to retrive content
     * @param int $ttl Cache time to live
     * @param bool $force Force cache update
     */
    public function __construct($url, $ttl = 0, $force = false) {
        $this->_url = $url;
        $this->_ttl = $ttl;
        $this->_force = $force;

        $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        $this->_userAgent = $userAgent;

        // issue #46914 - auto clean cache
        if (rand(0, 20) == 1) {
            $this->cleanCache();
        }
    }

    public function setUserAgent($userAgent) {
        $this->_userAgent = $userAgent;
    }

    public function setProxy($proxy) {
        $this->_proxy = $proxy;
    }

    public function setSocksProxy($proxy) {
        $this->_proxySocks = $proxy;
    }

    public function cleanCache() {
        // max cache time = 24h
        $maxTTL = 24*60*60;

        $cacheDir = scandir(__DIR__.'/cache/');
        unset($cacheDir[0]);
        unset($cacheDir[1]);
        foreach ($cacheDir as $dir) {
            // skip .htacces, .gitignore and other .xxx
            if (preg_match("/^\./ius", $dir)) {
                continue;
            }
            $path2 = scandir(__DIR__.'/cache/'.$dir);
            unset($path2[0]);
            unset($path2[1]);
            foreach ($path2 as $dir2) {
                $path3 = scandir(__DIR__.'/cache/'.$dir.'/'.$dir2);
                unset($path3[0]);
                unset($path3[1]);

                foreach ($path3 as $file) {
                    // skip no-MD5 files
                    if (strlen($file) != 32) {
                        continue;
                    }
                    $f = __DIR__.'/cache/'.$dir.'/'.$dir2.'/'.$file;
                    $time = filemtime($f);
                    // remove old files
                    if ($time < time() - $maxTTL) {
                        @unlink($f);
                    }
                }
            }
        }
    }

    /**
     * Получить данные из кеша или запроса
     *
     * @param string $text
     *
     * @return string
     */
    public function process($text) {
        $hash = md5($this->_url);
        $cacheFile = __DIR__.'/cache/';
        $folder1 = substr($hash, 0, 2);
        $folder2 = substr($hash, 2, 2);

        @mkdir($cacheFile.$folder1);
        @mkdir($cacheFile.$folder1.'/'.$folder2);

        $cacheFile = $cacheFile.$folder1.'/'.$folder2.'/'.$hash;

        // load from cache
        if (!$this->_force) {
            if (file_exists($cacheFile)) {
                if (filemtime($cacheFile) + $this->_ttl > time()) {
                    return file_get_contents($cacheFile);
                }
            }
        }

        $data = $this->_getData($this->_url);

        if ($this->_ttl) {
            file_put_contents($cacheFile, $data, LOCK_EX);
        }
        return $data;
    }

    /**
     * Retrieve data from URL
     *
     * @param string $url
     * return string
     */
    protected function _getData($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_USERAGENT, $this->_userAgent);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($this->_proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $this->_proxy);
        }
        if ($this->_proxySocks) {
            curl_setopt($ch, CURLOPT_PROXY, $this->_proxy);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    private $_userAgent;

    private $_proxy;

    private $_proxySocks;

    private $_url;

    private $_ttl;

    private $_force;

}