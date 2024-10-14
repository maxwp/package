<?php
class EE_Network {

    public function __construct(Connection_IConnection $connection) {
        $this->_connection = $connection;
    }

    public function execute($channel, $content, $argumentArray, $timeout = 10) {
        $hash = hash('murmur3f', $channel.$content.microtime(true).rand());

        $requestArray = [];
        $requestArray['content'] = $content;
        $requestArray['argumentArray'] = $argumentArray;
        $requestArray['ts_request'] = microtime(true);
        $requestArray['hash'] = $hash;
        $requestArray['timeout'] = $timeout;

        $redis = $this->_connection->getLink();

        $redis->publish($channel, json_encode($requestArray));

        while ($x = $redis->brPop($hash, $timeout)) {
            $r = json_decode($x[1], true);
            return $r;
        }

        throw new EE_Exception("Network timout for $channel:$hash");
    }

    /**
     * @var Connection_IConnection
     */
    private $_connection = null;

}