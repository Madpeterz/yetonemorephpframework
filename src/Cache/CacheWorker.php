<?php

namespace YAPF\Framework\Cache;

use YAPF\Framework\Cache\Drivers\Framework\CacheDriver;
use YAPF\Framework\Responses\Cache\PurgeReply;
use YAPF\Framework\Responses\Cache\StatsReply;

class CacheWorker extends CacheLinkDriver
{
    public function __construct(CacheDriver $driver, ?string $prefix = null)
    {
        $this->driver = $driver;
        if ($prefix != null) {
            $this->keyPrefix = $prefix;
        }
        $this->startup();
    }

    public function getStats(): StatsReply
    {
        return new StatsReply($this->itemReads, $this->itemWrites, $this->itemDeletes, $this->itemMiss);
    }

    public function setKeySuffix(string $suffix): void
    {
        $this->keySuffix = $suffix;
    }

    public function __destruct()
    {
        $this->shutdown();
    }

    protected function startup(): void
    {
        $reply = $this->driver->start();
        if ($reply->status == false) {
            $this->addError("cache startup error: " . $reply->message);
            $this->driver = null;
            return;
        }
        $key = $this->keyPrefix . "tablesChangedInfo" . $this->keySuffix;
        $reply = $this->driver->readKey($key);
        if ($reply->status == true) {
            $this->tablesLastChanged = json_decode($reply->value, true);
        }
    }

    public function save(): bool
    {

        $key = $this->keyPrefix . "tablesChangedInfo" . $this->keySuffix;
        $reply = $this->driver->writeKey($key, json_encode($this->tablesLastChanged), time() + (15 * 60));
        if ($reply->status == false) {
            $this->addError("Failed to write tablesChangedInfo on key: " . $key . " because:" . $reply->message);
            return false;
        }
        $reply = $this->driver->deleteKeys(array_keys($this->pendingDeleteKeys));
        if ($reply->status == false) {
            $this->addError($reply->message);
            return false;
        }
        $this->pendingDeleteKeys = [];
        foreach ($this->pendingWriteKeys as $key => $table) {
            $reply = $this->driver->writeKey(
                $key,
                $this->keys[$key],
                time() + ($this->tableConfig[$table]["maxAge"] * 60)
            );
            if ($reply->status == false) {
                $this->addError($reply->message);
                return false;
            }
        }
        $this->pendingWriteKeys = [];
        return true;
    }

    public function purge(): PurgeReply
    {
        return $this->driver->purgeAllKeys();
    }

    public function shutdown(bool $enableSave = true): void
    {
        if ($enableSave == true) {
            // write pending changes
            $this->save();
        }
        // stop the driver
        $this->driver->stop();
    }

    public function getHash(
        string $table,
        int $numberOfFields,
        bool $asSingle,
        ?array $whereConfig = null,
        ?array $orderConfig = null,
        ?array $optionsConfig = null,
        ?array $basicConfig = null
    ): ?string {
        if ($this->tableUsesCache($table, $asSingle) == false) {
            return null;
        }
        $raw = $this->giveJsonEncoded($whereConfig);
        $raw .= $this->giveJsonEncoded($orderConfig);
        $raw .= $this->giveJsonEncoded($optionsConfig);
        $raw .= $this->giveJsonEncoded($basicConfig);
        $raw .= json_encode(["table" => $table, "fieldscount" => $numberOfFields]);
        return substr($this->sha256($raw . "cache"), 0, $this->driver->getKeyLength());
    }

    protected function giveJsonEncoded(?array $input): string
    {
        if ($input === null) {
            return "";
        }
        return json_encode($input);
    }
}
