<?php

namespace YAPF\Framework\Cache\Drivers\Framework;

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

    public function __destruct()
    {
        $this->stop();
    }
}
