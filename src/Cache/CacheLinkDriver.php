<?php

namespace YAPF\Framework\Cache;

use YAPF\Framework\Responses\Cache\DeleteReply;
use YAPF\Framework\Responses\Cache\ReadReply;
use YAPF\Framework\Responses\Cache\WriteReply;

abstract class CacheLinkDriver extends CacheTables
{
    protected string $keyPrefix = "";
    protected string $keySuffix = "";

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
        $data = $this->getItem($hash);
        if ($data->status == false) {
            return null;
        }
        return $this->tableUnpackString($table, $data->value);
    }

    public function writeHash(
        string $table,
        string $hash,
        array $data,
        bool $asSingle
    ): bool {
        if ($this->tableUsesCache($table, $asSingle) == false) {
            return false;
        }
        $data = $this->writeItem($hash, $this->tablePackString($table, $data), $table);
        return $data->status;
    }

    public function deleteHash(
        string $table,
        string $hash,
        bool $asSingle
    ): bool {
        if ($this->tableUsesCache($table, $asSingle) == false) {
            return false;
        }
        $data = $this->deleteItem($hash);
        return $data->status;
    }

    protected function deleteItem(string $key): DeleteReply
    {
        $keyPlus = $this->keyPrefix . $key . $this->keySuffix;
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

    protected function writeItem(string $key, string $value, string $table): WriteReply
    {
        $keyPlus = $this->keyPrefix . $key . $this->keySuffix;
        if (array_key_exists($keyPlus, $this->pendingDeleteKeys) == true) {
            unset($this->pendingDeleteKeys[$keyPlus]);
        }
        $this->pendingWriteKeys[$keyPlus] = $table;
        $this->loadKey($keyPlus, $value);
        return new WriteReply("added to write Q, call save to write", true);
    }

    protected function getItem(string $key): ReadReply
    {
        $keyPlus = $this->keyPrefix . $key . $this->keySuffix;
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
