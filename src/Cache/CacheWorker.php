<?php

namespace YAPF\Framework\Cache;

use YAPF\Framework\Cache\Framework\CacheDriver;
use YAPF\Framework\Responses\Cache\DeleteReply;
use YAPF\Framework\Responses\Cache\ReadReply;
use YAPF\Framework\Responses\Cache\WriteReply;

class CacheWorker
{
    public function __construct(CacheDriver $driver)
    {
        $this->driver = $driver;
    }
    protected string $keyPrefix = "";
    protected ?CacheDriver $driver = null;

    public function __destruct()
    {
        $this->shutdown();
    }
    public function &getDriver(): CacheDriver
    {
        return $this->driver;
    }

    public function shutdown(): void
    {
        // write pending changes

        // stop the driver
        $this->driver->stop();
    }

    protected function deleteItem(string $key): DeleteReply
    {
        $keyPlusPrefix = $this->keyPrefix . $key;
        if ($this->haveDriver() == false) {
            return new DeleteReply("No cache driver");
        }
        if (array_key_exists($keyPlusPrefix, $this->pendingDeleteKeys) == true) {
            return new DeleteReply("key already marked as deleted", true);
        }
        if (array_key_exists($keyPlusPrefix, $this->pendingWriteKeys) == true) {
            unset($this->pendingWriteKeys[$keyPlusPrefix]);
            unset($this->keys[$keyPlusPrefix]);
        }
        $this->pendingDeleteKeys[$keyPlusPrefix] = true;
        return new DeleteReply("key marked to be removed", true);
    }
    protected function writeItem(string $key, string $value): WriteReply
    {
        $keyPlusPrefix = $this->keyPrefix . $key;
        if ($this->haveDriver() == false) {
            return new WriteReply("No cache driver");
        }
        if (array_key_exists($keyPlusPrefix, $this->pendingDeleteKeys) == true) {
            unset($this->pendingDeleteKeys[$keyPlusPrefix]);
        }
        $this->pendingWriteKeys[$keyPlusPrefix] = true;
        $this->loadKey($keyPlusPrefix, $value);
        return new WriteReply("added to write Q, call save to write", true);
    }
    protected function getItem(string $key): ReadReply
    {
        $keyPlusPrefix = $this->keyPrefix . $key;
        if ($this->haveDriver() == false) {
            return new ReadReply("No cache driver");
        }
        if (array_key_exists($key, $this->pendingDeleteKeys) == true) {
            return new ReadReply("key marked as deleted");
        }
        if ($this->seenKey($keyPlusPrefix) == true) {
            return new ReadReply(
                message:"from keys DB",
                value:$this->keys[$keyPlusPrefix],
                status:true
            );
        }
        $reply = $this->driver->readKey($keyPlusPrefix);
        if ($reply->status == false) {
            return $reply;
        }
        $this->loadKey($keyPlusPrefix, $reply->value);
        return new ReadReply("ok", $reply->value, true);
    }

    protected function haveDriver(): bool
    {
        if ($this->driver == null) {
            return false;
        }
        return $this->driver->connected();
    }

    // self store keys loaded in memory
    protected array $keys = [];
    protected array $pendingWriteKeys = [];
    protected array $pendingDeleteKeys = [];

    public function seenKey(string $key): bool
    {
        return array_key_exists($key, $this->keys);
    }

    public function loadKey(string $key, string $value): void
    {
        $this->keys[$key] = $value;
    }

    public function removeKey(string $key): bool
    {
        if ($this->seenKey($key) == false) {
            return false;
        }
        unset($this->keys[$key]);
        return true;
    }
}
