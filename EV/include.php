<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

ClassLoader::Get()->registerClass(__DIR__.'/EV.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EV_Exception.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/EV_IHandler.interface.php');
ClassLoader::Get()->registerClass(__DIR__.'/EV_Internal.class.php');

// внутрення шина нужна всегда
EV::Register(EV::EV_INTERNAL, new EV_Internal());