<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */
/**
 * Интерфейс для LinkMaker'a
 *
 * @copyright WebProduction
 * @package Engine
 * @author Max
 * @subpackage LinkMaker
 */
interface Engine_ILinkMaker {

    /**
     * Построить URL на контент
     *
     * @param mixed $contentID
     * @return string
     */
    public function makeURLByContentID($contentID);

    /**
     * Построить URL на контент с параметрами
     *
     * @param mixed $contentID
     * @param array $paramsArray
     * @return string
     */
    public function makeURLByContentIDParams($contentID, $paramsArray);

    /**
     * Построить URL на контент с одним параметром
     *
     * @param int $contentID
     * @param string $value
     * @param string $key
     * @return string
     */
    public function makeURLByContentIDParam($contentID, $value, $key = 'id');

    /**
     * Построить URL переписав/дописав у него параметры
     *
     * @param string $url
     * @param array $paramsArray
     * @return string
     */
    public function makeURLByReplaceParams($url, $paramsArray);

    /**
     * Построить URL, взяв за основу текущий URL и переписав/дописав к нему параметры
     *
     * @param array $urlParamsArray
     * @return string
     */
    public function makeURLCurrentByReplaceParams($urlParamsArray);

}