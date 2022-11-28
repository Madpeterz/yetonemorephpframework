<?php

namespace YAPF\Framework\Cache\Drivers\Redis;

use Throwable;
use YAPF\Framework\Responses\Cache\CacheStatusReply;
use YAPF\Framework\Responses\Cache\ListKeysReply;
use YAPF\Framework\Responses\Cache\ReadReply;

abstract class Read extends Core
{
    public function readKey(string $key): ReadReply
    {
        if ($this->readyToTakeAction() == false) {
            return new ReadReply($this->getLastErrorBasic(), $key);
        }
        if (strlen($key) > 1000) {
            return new ReadReply("Invaild key length [max 1000]");
        }
        try {
            $value = $this->client->get($key);
            $this->keyReads++;
            if ($value == null) {
                return new ReadReply("null result");
            }
            return new ReadReply("ok", $value, true);
        } catch (Throwable $ex) {
            $this->addError("Failed to read key: " . $ex->getMessage());
            $this->disconnected = true;
        }
        return new ReadReply($this->getLastErrorBasic());
    }

    public function listKeys(): ListKeysReply
    {
        if ($this->readyToTakeAction() == false) {
            return new ListKeysReply($this->getLastErrorBasic());
        }
        try {
            $reply = $this->client->keys("*");
            if ($reply != null) {
                return new ListKeysReply("ok", $reply, true);
            }
            return new ListKeysReply("keys list is null", status:true);
        } catch (Throwable $ex) {
            $this->addError($ex->getMessage());
        }
        return new ListKeysReply($this->getLastErrorBasic(), status:true);
    }

    public function hasKey(string $key): CacheStatusReply
    {
        if ($this->readyToTakeAction() == false) {
            return new CacheStatusReply($this->getLastErrorBasic());
        }
        try {
            if ($this->client->exists($key) == 1) {
                return new CacheStatusReply("ok", true);
            }
        } catch (Throwable $ex) {
            $this->addError($ex->getMessage());
        }
        return new CacheStatusReply($this->getLastErrorBasic());
    }
}
