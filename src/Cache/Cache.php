<?php

namespace YAPF\Cache;

abstract class Cache extends CacheWorker implements CacheInterface
{
    public function __destruct()
    {
        $this->shutdown();
    }

    /*
        start
        starts the cache service with a option of self cleaning.

        if self cleaning is enabled, try to remove 5 entrys on each load.
        for redis cache this should be false.
        if you have a cronjob cleaning up this should be false.
    */
    public function start(bool $selfCleanup = false): void
    {
        $this->setupCache();
        $this->intLastChanged();
        $this->loadLastChanged();
        if ($selfCleanup == true) {
            $this->cleanup(5);
        }
    }

    public function cleanup(int $max_counter = 5): void
    {
        $keys = $this->getKeys();
        $this->removed_counters = 0;
        foreach ($keys as $key) {
            if ($this->removed_counters >= $max_counter) {
                break;
            }
            $info_file = $this->getKeyInfo($key);
            if (array_key_exists("expires", $info_file) == false) {
                // key is broken (dat but no inf)
                $this->removeKey($key);
                continue;
            }
            if (array_key_exists($info_file["tableName"], $this->tableLastChanged) == false) {
                // table is not tracked by cache remove entry
                $this->removeKey($key);
                continue;
            }
            if ($info_file["unixtime"] < $this->tableLastChanged[$info_file["tableName"]]) {
                if ($info_file["allowChanged"] == false) {
                    // table has changed from cache creation remove entry.
                    $this->removeKey($key);
                    continue;
                }
            }
            if ($info_file["expires"] < time()) {
                // cache has expired.
                $this->removeKey($key);
                continue;
            }
        }
    }

    public function shutdown(): void
    {
        $this->saveLastChanged();
    }

    public function setAccountHash(string $acHash): void
    {
        if (strlen($acHash) > 5) {
            $acHash = substr($acHash, 0, 5);
        }
        $this->accountHash = $acHash;
    }

    /*
        addTableToCache
        - Tables that might have personalized data inside should not be
        set to shared mode and setAccountHash should be setup with a
        idhash for the account!

        longer autoExpire times will reduce the number of DB calls
        as long as nothing is changed in that table.

        tables that change Alot and need to always be current should avoid the
        cache.
    */
    public function addTableToCache(string $tablename, int $autoExpireMins = 15, bool $sharedDataset = false): void
    {
        $this->tablesConfig[$tablename] = [
            "shared" => $sharedDataset,
            "autoExpire" => $autoExpireMins,
        ];
    }

    public function markChangeToTable(string $tableName): void
    {
        if (array_key_exists($tableName, $this->tableLastChanged) == true) {
            $this->tableLastChanged[$tableName] = time();
            $this->lastChangedUpdated = true;
            if (in_array($tableName, $this->changedTables) == false) {
                $this->changedTables[] = $tableName;
            }
        }
    }

    public function cacheVaild(string $tableName, string $hash): bool
    {
        if (array_key_exists($tableName, $this->tablesConfig) == false) {
            return false; // not a table supported by cache
        }
        if (array_key_exists($tableName, $this->tableLastChanged) == false) {
            return false; // last changed entry missing (maybe its new)
        }
        if (in_array($tableName, $this->changedTables) == true) {
            return false; // table has had a change at some point miss the cache for now
        }
        $info_file = $this->getHashInfo($tableName, $hash);
        if (array_key_exists("expires", $info_file) == false) {
            return false;
        }
        if ($info_file["expires"] < time()) {
            $this->removeKey($this->getkeyPath($tableName, $hash));
            return false; // cache has expired
        }
        if ($info_file["unixtime"] < $this->tableLastChanged[$tableName]) {
            if ($info_file["allowChanged"] == false) {
                // cache is old
                $this->removeKey($this->getkeyPath($tableName, $hash));
                return false;
            }
        }
        return true; // cache is vaild
    }

    /**
     * readHash
     * attempts to read the cache for the selected mapping.
     * @return mixed[] [id => [key => value,...], ...]
    */
    public function readHash(string $tableName, string $hash): array
    {
        return json_decode($this->readKey($this->getkeyPath($tableName, $hash) . ".dat"), true);
    }
}
