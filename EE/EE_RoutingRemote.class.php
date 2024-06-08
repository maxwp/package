<?php
class EE_RoutingRemote implements EE_IRouting {

    public function matchClassName(EE_IRequest $request) {
        return $request->getArgument('ee-content');
    }

}