<?php

namespace YAPF\Framework\Cache;

use YAPF\Framework\Cache\Framework\CacheDriver;
use YAPF\Framework\Responses\Cache\DeleteReply;
use YAPF\Framework\Responses\Cache\ReadReply;
use YAPF\Framework\Responses\Cache\WriteReply;

abstract class CacheLinkDriver extends CacheTables
{
    protected string $keyPrefix = "";
    protected string $keySuffix = "";
    protected ?CacheDriver $driver = null;
    public function &getDriver(): CacheDriver
    {
        return $this->driver;
    }
    protected function haveDriver(): bool
    {
        if ($this->driver == null) {
            return false;
        }
        return $this->driver->connected();
    }
    /**
     * It reads a hash from the cache
     * @param string table The name of the table you want to read from.
     * @param string hash The hash to read from the table.
     * @param bool asSingle If true, the table is a single row table. If false, the table is a multi-row
     * table.
     * @return ?mixed[] The data from the cache.
     */
    public function readHash(string $table, string $hash, bool $asSingle = true): ?array
    {
        if ($this->tableUsesCache($table, $asSingle) == false) {
            return null;
        }
        $data = $this->getItem($table . $hash);
        if ($data->status == false) {
            return null;
        }
        return $this->tableUnpackString($table, $data->value);
    }

    public function writeHash(
        string $table,
        string $hash,
        array $data,
        bool $asSingle = true
    ): bool {
        if ($this->tableUsesCache($table, $asSingle) == false) {
            return null;
        }
        $data = $this->writeItem($table . $hash, $this->tablePackString($table, $data));
        return $data->status;
    }

    protected function deleteItem(string $key): DeleteReply
    {
        $keyPlus = $this->keyPrefix . $key . $this->keySuffix;
        if ($this->haveDriver() == false) {
            return new DeleteReply("No cache driver");
        }
        if (array_key_exists($keyPlus, $this->pendingDeleteKeys) == true) {
            return new DeleteReply("key already marked as deleted", true);
        }
        if (array_key_exists($keyPlus, $this->pendingWriteKeys) == true) {
            unset($this->pendingWriteKeys[$keyPlus]);
            unset($this->keys[$keyPlus]);
        }
        $this->pendingDeleteKeys[$keyPlus] = true;
        return new DeleteReply("key marked to be removed", true);
    }

    protected function writeItem(string $key, string $value): WriteReply
    {
        $keyPlus = $this->keyPrefix . $key . $this->keySuffix;
        if ($this->haveDriver() == false) {
            return new WriteReply("No cache driver");
        }
        if (array_key_exists($keyPlus, $this->pendingDeleteKeys) == true) {
            unset($this->pendingDeleteKeys[$keyPlus]);
        }
        $this->pendingWriteKeys[$keyPlus] = true;
        $this->loadKey($keyPlus, $value);
        return new WriteReply("added to write Q, call save to write", true);
    }
    protected function getItem(string $key): ReadReply
    {
        $keyPlus = $this->keyPrefix . $key . $this->keySuffix;
        if ($this->haveDriver() == false) {
            return new ReadReply("No cache driver");
        }
        if (array_key_exists($keyPlus, $this->pendingDeleteKeys) == true) {
            return new ReadReply("key marked as deleted");
        }
        if ($this->seenKey($keyPlus) == true) {
            return new ReadReply(
                message:"from keys DB",
                value:$this->keys[$keyPlus],
                status:true
            );
        }
        $reply = $this->driver->readKey($keyPlus);
        if ($reply->status == false) {
            return $reply;
        }
        $this->loadKey($keyPlus, $reply->value);
        return new ReadReply("ok", $reply->value, true);
    }
}
