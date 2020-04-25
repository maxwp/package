<?php
/**
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package Package
 */
class APIServer {

    /**
     * Получить значение аргумента,
     * который может быть передан через GET, POST, JSON POST, CLI arg, X-HEADER
     *
     * @param string $key
     * @return mixed
     */
    public function getArgument($key) {
        $x = $this->getArgumentSecure($key);
        if (!$x) {
            throw new APIServer_Exception('No argument '.$key);
        }
        return $x;
    }

    /**
     * Получить значение аргумента безопастно (без exception),
     * который может быть передан через GET, POST, JSON POST, CLI arg, X-HEADER
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getArgumentSecure($key, $default = false) {
        global $_GET;
        global $_POST;
        global $_SERVER;
        global $argv;

        $input = file_get_contents('php://input');
        $json = (array) json_decode($input, true);

        $value = false;

        if (isset($json[$key])) {
            $value = $json[$key];
        } elseif (isset($_GET[$key])) {
            $value = $_GET[$key];
        } elseif (isset($_POST[$key])) {
            $value = $_POST[$key];
        } elseif (isset($_SERVER['HTTP_X_'.strtoupper($key)])) {
            $value = $_SERVER['HTTP_X_'.strtoupper($key)];
        } else {
            for ($j = 1; $j <= 100; $j++) {
                $arg = @$argv[$j];
                if (!$arg) {
                    continue;
                }
                $arg = explode('=', $arg, 2);
                if ($arg[0] == $key) {
                    $value = $arg[1];
                    break;
                }
            }
        }

        if (!$value) {
            $value = $default;
        }

        return $value;
    }

    /**
     * Отдать успешное сообщение с данными
     *
     * @param array $dataArray
     */
    public function responseSuccess($dataArray) {
        $a = array(
        'status' => 'success',
        'resultArray' => $dataArray,
        );

        header('Content-type: application/json');
        print json_encode($a);
        exit();
    }

    /**
     * Отдать сообщение с ошибкой
     *
     * @param Exception $e
     */
    public function responseError(Exception $e) {
        $errorArray = array();

        if ($e instanceof ExceptionAPI) {
            $errorArray = $e->getErrorArray();
        } elseif ($e instanceof APIServer_Exception) {
            $errorArray = $e->getErrorArray();
        } else {
            $errorArray = array($e->getMessage());
        }

        $a = array(
        'status' => 'error',
        'errorArray' => $errorArray,
        );

        header('Content-type: application/json');
        print json_encode($a);
        exit();
    }

    /**
     * @return APIServer
     */
    public static function Get() {
        if (!self::$_Instance) {
            self::$_Instance = new self();
        }

        return self::$_Instance;
    }

    private static $_Instance = false;

    private function __construct() {

    }

}