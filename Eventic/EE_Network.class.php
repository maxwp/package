<?php
class EE_Network {

    public function __construct(ConnectionManager_IConnection $connection) {
        $this->_connection = $connection;
    }

    public function execute($channel, $content, $argumentArray, $timeout = 10) {
        $hash = md5($channel.$content.microtime(true).rand());

        $requestArray = [];
        $requestArray['content'] = $content;
        $requestArray['argumentArray'] = $argumentArray;
        $requestArray['ts'] = microtime(true);
        $requestArray['hash'] = $hash;
        $requestArray['timeout'] = $timeout;

        $redis = $this->_connection->getLinkID();

        $redis->publish($channel, json_encode($requestArray));

        while ($x = $redis->brPop($hash, $timeout)) {
            $r = json_decode($x[1], true);
            return $r;
        }

        throw new EE_Exception("Network timout for $channel:$hash");
    }

    /**
     * @var ConnectionManager_IConnection
     */
    private $_connection = null;

}