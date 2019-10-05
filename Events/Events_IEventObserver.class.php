<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2013 WebProduction <webproduction.ua>
 *
 * This program is commetcial software; you can not redistribute it and/or
 * modify it under any terms.
 */

/**
 * Интерфейс наблюдателя события для Events
 *
 * @author Maxim Miroshnichenko <max@webproduction.com.ua>
 * @copyright WebProduction
 * @package Events
 */
interface Events_IEventObserver {

	public function notify(Events_Event $event);

}