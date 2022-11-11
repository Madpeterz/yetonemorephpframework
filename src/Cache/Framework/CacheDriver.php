<?php

namespace YAPF\Framework\Cache\Framework;

use YAPF\Core\ErrorControl\ErrorLogging;
use YAPF\Framework\Responses\Cache\CacheStatusReply;
use YAPF\Framework\Responses\Cache\StatsReply;

abstract class CacheDriver extends ErrorLogging implements CacheInterface
{
    protected bool $disconnected = true;
    protected int $keyDeletes = 0;
    protected int $keyWrites = 0;
    protected int $keyReads = 0;

    public function getStats(): StatsReply
    {
        return new StatsReply($this->keyReads, $this->keyWrites, $this->keyDeletes);
    }
    public function connected(): bool
    {
        return !$this->disconnected;
    }

    protected function readyToTakeAction(): bool
    {
        if ($this->disconnected == true) {
            $this->addError("marked as disconnected");
            return false;
        }
        return true;
    }

        /**
     * It sets the connection settings to use a unix socket, and then starts the connection
     * @return CacheStatusReply A CacheStatusReply object.
     */
    public function connectUnix(string $unixSocket): CacheStatusReply
    {
        return new CacheStatusReply("Unix socket Not supported for this driver");
    }


    /**
     * It sets the connection settings to use TCP, and then starts the connection
     * @return CacheStatusReply A CacheStatusReply object.
     */
    public function connectTCP(string $serverIP = "127.0.0.1", int $serverPort = 6379): CacheStatusReply
    {
        return new CacheStatusReply("TCP Not supported for this driver");
    }

    protected function __destruct()
    {
        $this->stop();
    }
}
