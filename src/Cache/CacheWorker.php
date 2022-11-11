<?php

namespace YAPF\Framework\Cache;

use YAPF\Framework\Cache\Framework\CacheDriver;

abstract class CacheWorker extends CacheLinkDriver
{
    public function __construct(CacheDriver $driver)
    {
        $this->driver = $driver;
    }

    public function __destruct()
    {
        $this->shutdown();
    }

    public function shutdown(): void
    {
        // write pending changes

        // stop the driver
        $this->driver->stop();
    }

    public function getHash(
        ?array $whereConfig = null,
        ?array $orderConfig = null,
        ?array $optionsConfig = null,
        ?array $basicConfig = null,
        string $table,
        int $numberOfFields,
        bool $asSingle = true
    ): ?string {
        if ($this->haveDriver() == false) {
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

    public function cacheValid(string $table, string $hash, bool $asSingle = true): bool
    {
        if ($this->tableUsesCache($table, $asSingle) == false) {
            return false;
        }
        return $this->getItem($table . $hash)->status;
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
}
