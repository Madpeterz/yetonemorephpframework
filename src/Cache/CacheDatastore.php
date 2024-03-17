<?php

namespace YAPF\Framework\Cache;

use YAPF\Framework\Cache\Drivers\Framework\CacheDriver;
use YAPF\Framework\Helpers\FunctionHelper;
use YAPF\Framework\Responses\Cache\WriteReply;

abstract class CacheDatastore extends FunctionHelper
{
    protected ?CacheDriver $driver = null;
    public function &getDriver(): CacheDriver
    {
        return $this->driver;
    }
    protected function getDriverConnected(): bool
    {
        return $this->driver->connected();
    }

    protected ?array $tablesLastChanged = null;
    /*
        lastUpdate => unixtime
        table
            => last update
    */

    protected array $tableConfig = [];
    // table => singles [true|false], sets [true|false], encryptData [true|false], maxAge [int mins]

    // self store keys loaded in memory
    protected array $keys = [];
    protected array $pendingWriteKeys = [];
    protected array $pendingDeleteKeys = [];


    protected int $itemReads = 0;
    protected int $itemWrites = 0;
    protected int $itemDeletes = 0;
    protected int $itemMiss = 0;

    protected function seenKey(string $key): bool
    {
        return array_key_exists($key, $this->keys);
    }

    protected function loadKey(string $key, string $value): void
    {
        $this->keys[$key] = $value;
    }
}
