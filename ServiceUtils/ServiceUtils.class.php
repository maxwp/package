<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2015 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * ServiceUtils.
 * В новой концепции - линейный реестр сервисов (классов).
 * В старой концепции - фабрика сервисов
 *
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @author DFox
 * @copyright WebProduction
 */
class ServiceUtils {

    /**
     * Получить Service Storage
     *
     * @return ServiceUtils
     */
    public static function Get() {
        if (!self::$_Instance) {
        	self::$_Instance = new self();
        }
        return self::$_Instance;
    }

    /**
     * Получить сервис
     *
     * @param string $serviceName
     * @return Object
     */
    public function getService($serviceName) {
        $serviceName = trim($serviceName);
        if (!$serviceName) {
        	throw new ServiceUtils_Exception('Empty serviceName');
        }

        if (!isset($this->_objectArray[$serviceName])) {

            if (!isset($this->_serviceArray[$serviceName])) {
            	throw new ServiceUtils_Exception('Invalid serviceName');
            }

            $className = $this->_serviceArray[$serviceName];
        	$this->_objectArray[$serviceName] = new $className();
        }

        return $this->_objectArray[$serviceName];
    }

    /**
     * Задать сервис
     *
     * @param string $serviceName
     * @param string $className
     */
    public function setService($serviceName, $className) {
        $serviceName = trim($serviceName);
        if (!$serviceName) {
        	throw new ServiceUtils_Exception('Empty serviceName');
        }
        if (!$className) {
        	throw new ServiceUtils_Exception('Empty className');
        }

        $this->_serviceArray[$serviceName] = $className;
        unset($this->_objectArray[$serviceName]);
    }

    private static $_Instance = null;

    private $_serviceArray = array();

    private $_objectArray = array();

}