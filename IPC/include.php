<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

ClassLoader::Get()->registerClass(__DIR__.'/IPC.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/IPC_Addressing.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/IPC_Semaphore.class.php');
ClassLoader::Get()->registerClass(__DIR__.'/IPC_Memory.class.php');