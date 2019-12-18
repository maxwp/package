<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commetcial software; you can not redistribute it and/or
 * modify it under any terms.
 */

/**
 * Events
 *
 * @author Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package Events
 */

ClassLoader::Get()->registerClass(__DIR__.'/Events.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Events_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Events_Event.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Events_IEventObserver.class.php');