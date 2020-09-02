<?php
/**
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package Package
 */
class APIClient {

    public function requestDebug($method, $postArray = array(), $autoToken = true, $allowCache = false) {
        return $this->request($method, $postArray, $autoToken, $allowCache, true);
    }

    public function request($method, $postArray = array(), $autoToken = true, $allowCache = false, $debug = false) {
        if ($allowCache) {
            $cacheKey = md5($method.serialize($postArray));
            if (!empty($this->_cacheArray[$cacheKey])) {
                return $this->_cacheArray[$cacheKey];
            }
        }

        $ch = curl_init();

        if ($autoToken && class_exists('UserService')) {
            try {
                $postArray['token'] = UserService::Get()->getToken();
            } catch (Exception $e) {

            }
        }

        curl_setopt($ch, CURLOPT_URL, $this->_apiURL.$method);
        if ($postArray) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postArray);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);

        curl_close($ch);

        if ($debug) {
            print_r($server_output);
        }

        if (!$server_output) {
            throw new Exception('Invalid API responce', 1);
        }

        $data = json_decode($server_output, true);
        if (@$data['status'] != 'success') {
            throw new APIClient_Exception($data['errorArray']);
        }

        if (!isset($data['resultArray'])) {
            throw new APIClient_Exception('No result in responce');
        }

        $resultArray = $data['resultArray'];

        if ($allowCache) {
            $this->_cacheArray[$cacheKey] = $resultArray;
        }

        return $resultArray;
    }

    private function __construct($apiURL) {
        if (!$apiURL) {
            throw new APIClient_Exception('Invalid apiURL');
        }

        $this->_apiURL = $apiURL;
    }

    /**
     * @return APIClient
     */
    public static function Init($apiURL, $apiKey = 'default') {
        self::$_InstanceArray[$apiKey] = new self($apiURL);
        return self::$_InstanceArray[$apiKey];
    }

    /**
     * @return APIClient
     */
    public static function Get($apiKey = 'default') {
        if (!isset(self::$_InstanceArray[$apiKey])) {
            throw new APIClient_Exception('No apiKey '.$apiKey);
        }

        return self::$_InstanceArray[$apiKey];
    }

    private static $_InstanceArray = array();

    private $_apiURL = '';

    private $_cacheArray = array();

}