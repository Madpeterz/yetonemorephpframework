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

    public function purge(): bool
    {
        return false;
    }

    public function getChangeID(string $tableName): int
    {
        if (array_key_exists($tableName, $this->tableLastChanged) == false) {
            $this->addErrorlog("getChangeID: " . $tableName . " is not tracked");
            return 0;
        }
        return $this->tableLastChanged[$tableName];
    }

    public function cleanup(int $max_counter = 5): void
    {
        $keys = $this->getKeys();
        $this->addErrorlog("cleanup: got keys: " . json_encode($keys));
        $this->removed_counters = 0;
        foreach ($keys as $key) {
            if ($this->removed_counters >= $max_counter) {
                break;
            }
            $this->addErrorlog("cleanup: fetching info for: " . $key);
            $info_file = $this->getKeyInfo($key);
            if (array_key_exists("expires", $info_file) == false) {
                // key is broken (dat but no inf)
                $this->addErrorlog("Cleanup: removing " . $key . " missing expires");
                $this->removeKey($key);
                continue;
            }
            if (array_key_exists($info_file["tableName"], $this->tableLastChanged) == false) {
                // table is not tracked by cache remove entry
                $this->addErrorlog("Cleanup: removing " . $key . " table " . $info_file["tableName"] . " not tracked");
                $this->removeKey($key);
                continue;
            }
            if ($info_file["changeID"] != $this->tableLastChanged[$info_file["tableName"]]) {
                if ($info_file["allowChanged"] == false) {
                    // table has changed from cache creation remove entry.
                    $this->addErrorlog("Cleanup: removing " . $key . " table has changes");
                    $this->removeKey($key);
                    continue;
                }
            }
            $dif = $info_file["expires"] - time();
            if ($dif < 0) {
                // cache has expired.
                $this->addErrorlog("Cleanup: removing " . $key . " cache has expired");
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
    public function addTableToCache(string $tableName, int $autoExpireMins = 15, bool $sharedDataset = false): void
    {
        $this->addErrorlog("addTableToCache: enabled cache for: " . $tableName . " with expires: " . $autoExpireMins);
        $this->tablesConfig[$tableName] = [
            "shared" => $sharedDataset,
            "autoExpire" => $autoExpireMins,
        ];
    }

    public function markChangeToTable(string $tableName): void
    {
        if (array_key_exists($tableName, $this->tableLastChanged) == false) {
            $this->addErrorlog("markChangeToTable: " . $tableName . " is not tracked");
            return;
        }
        $this->tableLastChanged[$tableName] = $this->tableLastChanged[$tableName] + 1;
        if ($this->tableLastChanged[$tableName] >= 9999) {
            $this->tableLastChanged[$tableName] = 1;
        }
        $this->lastChangedUpdated = true;
        $this->addErrorlog("markChangeToTable: " . $tableName . " has changed");
        if (in_array($tableName, $this->changedTables) == false) {
            $this->changedTables[] = $tableName;
        }
    }

    public function cacheVaild(string $tableName, string $hash): bool
    {
        $this->addErrorlog("cacheVaild: checking: " . $tableName . " " . $hash);
        if (array_key_exists($tableName, $this->tablesConfig) == false) {
            $this->addErrorlog("cacheVaild: Table is not supported by cache");
            return false; // not a table supported by cache
        }
        if (array_key_exists($tableName, $this->tableLastChanged) == false) {
            $this->addErrorlog("cacheVaild: Table last changed is missed (new table?)");
            return false; // last changed entry missing (maybe its new)
        }
        if (in_array($tableName, $this->changedTables) == true) {
            $this->addErrorlog("cacheVaild: table has had changes from startup");
            return false; // table has had a change at some point miss the cache for now
        }
        $this->addErrorlog("cacheVaild: attempting to get info blob");
        $info_file = $this->getHashInfo($tableName, $hash);
        $this->addErrorlog("cacheVaild: info_file: " . json_encode($info_file));
        if (array_key_exists("expires", $info_file) == false) {
            $this->addErrorlog("cacheVaild: expires info is missing");
            $this->removeKey($this->getkeyPath($tableName, $hash));
            return false;
        }
        if ($info_file["expires"] < time()) {
            $this->addErrorlog("cacheVaild: entry has expired");
            $this->removeKey($this->getkeyPath($tableName, $hash));
            return false; // cache has expired
        }
        if ($info_file["changeID"] != $this->tableLastChanged[$tableName]) {
            if ($info_file["allowChanged"] == false) {
                // cache is old
                $this->addErrorlog("cacheVaild: entry is old");
                $this->removeKey($this->getkeyPath($tableName, $hash));
                return false;
            }
        }
        $this->addErrorlog("cacheVaild: ok");
        return true; // cache is vaild
    }

    /**
     * readHash
     * attempts to read the cache for the selected mapping.
     * @return mixed[] [id => [key => value,...], ...]
    */
    public function readHash(string $tableName, string $hash): array
    {
        $this->addErrorlog("readHash: " . $tableName . " " . $hash);
        return json_decode($this->readKey($this->getkeyPath($tableName, $hash) . ".dat"), true);
    }

    public function writeHash(string $tableName, string $hash, array $data, bool $allowChanged): bool
    {
        if (array_key_exists($tableName, $this->tablesConfig) == false) {
            return false;
        }
        $path = $this->getkeyPath($tableName, $hash);
        $info_file = [
        "changeID" => $this->tableLastChanged[$tableName],
        "expires" => time() + (60 * $this->tablesConfig["autoExpire"]),
        "allowChanged" => $allowChanged,
        "tableName" => $tableName,
        ];
        $writeOne = $this->writeKey($path . ".inf", json_encode($info_file), $tableName);
        $writeTwo = $this->writeKey($path . ".dat", json_encode($data), $tableName);
        if ($writeOne != $writeTwo) {
            $this->removeKey($path . ".inf");
            $this->removeKey($path . ".dat");
            return false;
        }
        return $writeOne;
    }

    /**
     * giveArrayOnNull
     * returns the input array or if that is null a empty array
     * @return mixed[]
     */
    protected function giveArrayOnNull(?array $input): array
    {
        if ($input == null) {
            return [];
        }
        return $input;
    }

    public function getHash(
        ?array $where_config,
        ?array $order_config,
        ?array $options_config,
        ?array $join_tables,
        string $tableName,
        int $fields
    ): string {
        $bit1 = sha1("bit1" . implode("|", $this->giveArrayOnNull($where_config)));
        $bit2 = sha1("bit2" . implode("|", $this->giveArrayOnNull($order_config)));
        $bit3 = sha1("bit3" . implode("|", $this->giveArrayOnNull($options_config)));
        $bit4 = sha1("bit4" . implode("|", $this->giveArrayOnNull($join_tables)));
        $shaHash = sha1(
            $bit1 .
            $bit2 .
            $bit3 .
            $bit4 .
            $this->getChangeID($tableName) .
            $tableName .
            $fields
        );
        return substr($shaHash, 0, 7);
    }
}
