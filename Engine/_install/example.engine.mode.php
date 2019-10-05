<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * В этом файле определяются устанавливается режим работы Engine
 */

PackageLoader::Get()->setMode('development');
PackageLoader::Get()->setMode('debug');

// Engine::Get()->setConfigField('project-host', 'www.localhost');

/*
PackageLoader::Get()->import('ConnectionManager');

// connection to database
ConnectionManager::Get()->addConnectionDatabase(
new ConnectionManager_MySQL(
'localhost',
'user',
'password',
'db'
));

*/