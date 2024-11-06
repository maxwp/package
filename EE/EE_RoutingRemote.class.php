<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Remote routing
 */
class EE_RoutingRemote implements EE_IRouting {

    public function matchContent(EE_IRequest $request) {
        return $request->getArgument('ee-content');
    }

}