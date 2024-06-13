<?php
class EE_RoutingCLI implements EE_IRouting {

    public function matchContent(EE_IRequest $request) {
        $a = $request->getArgumentArray();

        $class = @$a[1];
        if (!$class) {
            throw new EE_Exception("No class argv[1]");
        }

        return $class;
    }

}