<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

ClassLoader::Get()->registerClass(__DIR__.'/Events.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Events_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Events_Event.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/Events_IEventObserver.class.php');