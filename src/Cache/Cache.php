<?php

namespace YAPF\Framework\Cache;

use YAPF\Framework\Helpers\FunctionHelper;

abstract class Cache extends CacheWorker implements CacheInterface
{
    protected $tempStorage = [];
    protected bool $allowCleanup = false;


    protected int $readsCount = 0;
    protected int $writesCount = 0;
    protected int $missedExpiredCount = 0;
    protected int $missedNotFoundCount = 0;
    protected int $missedChangedCount = 0;
    protected int $missedNotUsedCount = 0;
    protected int $missedNoSingleCount  = 0;
    protected int $tableVersionUpdatesCount = 0;
    protected int $hitCount = 0;
    protected int $pendingWritesCount = 0;
    protected int $hashsCount = 0;
    protected string $driverName = "NoDriver";

    public function getDriverName(): string
    {
        return $this->driverName;
    }

    protected bool $disconnected = false;

    public function getStatusConnected(): bool
    {
        return $this->connected;
    }

    /**
     * statusCounters
     * @return mixed[]
    */
    public function getStatusCounters(): array
    {
        return [
            "config" => [
                "driver" => $this->driverName,
            ],
            "actions" => [
                "reads" => $this->readsCount,
                "writes" => $this->writesCount,
            ],
            "checks" => [
                "expired" => $this->missedExpiredCount,
                "notFound" => $this->missedNotFoundCount,
                "changed" => $this->missedChangedCount,
                "notUsed" => $this->missedNotUsedCount,
                "noSingles" => $this->missedNoSingleCount,
                "versionChanged" => $this->tableVersionUpdatesCount,
                "readQ" => $this->hitCount,
                "pendingW" => $this->pendingWritesCount,
                "hashs" => $this->hashsCount,
            ],
        ];
    }

    // writes cache to mem first, and then to disk at the end
    // saves unneeded writes if we make a change after loading.
    public function __destruct()
    {
        $this->addErrorlog("Shutting down:" . $this->getCacheUTimeID());
        $this->shutdown();
    }

    protected $myUtimeID = "";
    public function __construct()
    {
        $this->myUtimeID = microtime() . " " . rand(200, 1000);
    }

    public function getCacheUTimeID(): string
    {
        return $this->myUtimeID;
    }

    /**
     * getKeys
     * returns null on failed, otherwise an array of keys
     * @return mixed[]
     */
    public function getKeys(): ?array
    {
        return null;
    }

    /*
        start
        starts the cache service with a option of self cleaning.

        if self cleaning is enabled, try to remove 5 items on each load.
        for redis cache this should be false.
        if you have a cronjob cleaning up this should be false.
    */
    public function start(): void
    {
        if ($this->setupCache() == false) {
            $this->disconnected = true;
            $this->addErrorlog("Connection has dropped on setup of cache!");
            return;
        }
        $this->intLastChanged();
        $this->loadLastChanged();
    }

    protected function finalizeWrites(): void
    {
        if ($this->disconnected == true) {
            return;
        }
        $this->addErrorlog("Finalizing with: " . count($this->tempStorage) . " items");
        foreach ($this->tempStorage as $tmpKey => $dataset) {
            /*
            "key" => $key,
            "data" => $data,
            "table" => $table,
            "versionID" => time(),
            "expires" => int
            */
            if ($dataset["versionID"] != $this->tableLastChanged[$dataset["table"]]) {
                $this->addErrorlog("Skipping writing: " . json_encode($dataset) . " version has changed");
                continue; // skipped write, table changed from read
            }
            $status = $this->writeKeyReal($dataset["key"], $dataset["data"], $dataset["expires"]);
            if ($status == false) {
                $this->disconnected = true;
                $this->addErrorlog("Marking cache as disconnected (failed to write)");
                break;
            }
            $this->writesCount++;
            $this->markConnected();
        }
        $this->tempStorage = [];
    }

    public function purge(): bool
    {
        return false;
    }

    public function forceWrite(string $tableName, string $hash, string $info, string $data, int $expires): void
    {
        if ($this->disconnected == true) {
            return;
        }
        $this->addErrorlog("Warning calling forceWrite is a bad idea unless your in testing!");
        $key = $this->getKeyPath($tableName, $hash);
        $this->writeKeyReal($key . ".dat", $data, $expires);
        $this->writeKeyReal($key . ".inf", $info, $expires);
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
        if ($this->disconnected == true) {
            return;
        }
        if ($this->allowCleanup == false) {
            return;
        }
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
        if ($this->disconnected == true) {
            return;
        }
        $this->saveLastChanged();
        $this->finalizeWrites();
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
        $tablename: name of the table to enable the cache for.
        $autoExpireMins: How long in mins should the cache live for (unless there is a change)
        $sharedDataset: if true account hash is ignored and loads will be for everyone.
        $enableSingleLoads: if true single class loads will also hit the cache.

        - Tables that might have personalized data inside should not be
        set to shared mode and setAccountHash should be setup with a
        id hash for the account!

        longer autoExpire times will reduce the number of DB calls
        as long as nothing is changed in that table.

        tables that change often [more than once every 30 secs] and need to always be
        current should avoid the cache.

        $autoExpireMins of less than 3 are set to 3.
    */
    public function addTableToCache(
        string $tableName,
        int $autoExpireMins = 15,
        bool $sharedDataset = false,
        bool $enableSingleLoads = false
    ): void {
        $this->addErrorlog("addTableToCache: enabled cache for: " . $tableName . " with expires: " . $autoExpireMins);
        if ($autoExpireMins < 3) {
            $autoExpireMins = 3;
        }
        $this->tablesConfig[$tableName] = [
            "shared" => $sharedDataset,
            "autoExpire" => $autoExpireMins,
            "singlesEnabled" => $enableSingleLoads,
        ];
    }

    public function markChangeToTable(string $tableName): void
    {
        if (array_key_exists($tableName, $this->tableLastChanged) == false) {
            $this->addErrorlog("markChangeToTable: " . $tableName . " is not tracked");
            return;
        }
        $this->tableVersionUpdatesCount++;
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

    public function cacheValid(string $tableName, string $hash, bool $asSingle = false): bool
    {
        if ($this->disconnected == true) {
            return false;
        }
        $this->addErrorlog("cacheValid: checking: " . $tableName . " " . $hash);
        if (array_key_exists($tableName, $this->tablesConfig) == false) {
            $this->addErrorlog("cacheValid: Table is not supported by cache");
            $this->missedNotUsedCount++;
            return false; // not a table supported by cache
        }
        if (array_key_exists($tableName, $this->tableLastChanged) == false) {
            $this->addErrorlog("cacheValid: Table last changed is missed (new table?)");
            $this->missedChangedCount++;
            return false; // last changed entry missing (maybe its new)
        }
        if (in_array($tableName, $this->changedTables) == true) {
            $this->addErrorlog("cacheValid: table has had changes from startup");
            $this->missedChangedCount++;
            return false; // table has had a change at some point miss the cache for now
        }
        if ($asSingle == true) {
            if ($this->tablesConfig[$tableName]["singlesEnabled"] == false) {
                $this->addErrorlog("cacheValid: table " . $tableName . " does not allow singles");
                $this->missedNoSingleCount++;
                return false;
            }
        }
        $this->addErrorlog("cacheValid: attempting to get info blob");
        $info_file = $this->getHashInfo($tableName, $hash);
        $this->addErrorlog("cacheValid: info_file: " . json_encode($info_file));
        if (array_key_exists("expires", $info_file) == false) {
            $this->addErrorlog("cacheValid: expires info is missing");
            $this->missedNotFoundCount++;
            $this->removeKey($this->getKeyPath($tableName, $hash));
            return false;
        }
        if ($info_file["expires"] < time()) {
            $dif = time() - $info_file["expires"];
            $this->addErrorlog("cacheValid: entry has expired " . $dif . " secs ago");
            $this->missedExpiredCount++;
            $this->removeKey($this->getKeyPath($tableName, $hash));
            return false; // cache has expired
        }
        if ($info_file["changeID"] != $this->tableLastChanged[$tableName]) {
            if ($info_file["allowChanged"] == false) {
                // cache is old
                $this->addErrorlog("cacheValid: entry is old");
                $this->missedExpiredCount++;
                $this->removeKey($this->getKeyPath($tableName, $hash));
                return false;
            }
        }
        $this->markConnected();
        $this->hitCount++;
        $this->addErrorlog($this->driverName . " cacheValid: ok [" . $info_file["expires"] . " vs " . time() . " dif: "
        . (time() - $info_file["expires"]));
        return true; // cache is valid
    }

    /**
     * readHash
     * attempts to read the cache for the selected mapping.
     * @return mixed[] [id => [key => value,...], ...]
    */
    public function readHash(string $tableName, string $hash): ?array
    {
        if ($this->disconnected == true) {
            return null;
        }
        $this->addErrorlog("readHash: " . $tableName . " " . $hash);
        $key = $this->getKeyPath($tableName, $hash) . ".dat";
        if (in_array($key, $this->keyData) == true) {
            $this->markConnected();
            return json_decode($this->keyData[$key], true);
        }
        $reply = $this->readKey($key);
        if ($reply == null) {
            return null;
        }
        $this->readsCount++;
        $this->keyData[$key] = $reply;
        $this->markConnected();
        return json_decode($reply, true);
    }

    public function writeHash(string $tableName, string $hash, array $data, bool $allowChanged): bool
    {
        if ($this->disconnected == true) {
            return false;
        }
        if (array_key_exists($tableName, $this->tablesConfig) == false) {
            $this->addErrorlog("(writeHash) table " . $tableName . " is not supported");
            return false;
        }
        $path = $this->getKeyPath($tableName, $hash);
        $expiresUnixtime = time() + 10 + (60 * $this->tablesConfig[$tableName]["autoExpire"]);
        if ($expiresUnixtime < (time() + (60 * 3))) {
            $this->addErrorlog("Warning - short timer for table " . $tableName . " this should not be below 3 mins");
        }
        $info_file = [
        "changeID" => $this->tableLastChanged[$tableName],
        "expires" => $expiresUnixtime,
        "allowChanged" => $allowChanged,
        "tableName" => $tableName,
        ];
        $writeOne = $this->writeKey($path . ".inf", json_encode($info_file), $tableName, $expiresUnixtime);
        $writeTwo = $this->writeKey($path . ".dat", json_encode($data), $tableName, $expiresUnixtime);
        if ($writeOne != $writeTwo) {
            $this->removeKey($path . ".inf");
            $this->removeKey($path . ".dat");
            return false;
        }
        if ($writeOne == true) {
            $this->pendingWritesCount++;
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
        ?array $whereConfig,
        ?array $order_config,
        ?array $options_config,
        ?array $join_tables,
        string $tableName,
        int $fields
    ): string {
        $bit1 = FunctionHelper::sha256("bit1" . json_encode($this->giveArrayOnNull($whereConfig)));
        $bit2 = FunctionHelper::sha256("bit2" . json_encode($this->giveArrayOnNull($order_config)));
        $bit3 = FunctionHelper::sha256("bit3" . json_encode($this->giveArrayOnNull($options_config)));
        $bit4 = FunctionHelper::sha256("bit4" . json_encode($this->giveArrayOnNull($join_tables)));
        $shaHash = FunctionHelper::sha256(
            $bit1 .
            $bit2 .
            $bit3 .
            $bit4 .
            $this->getChangeID($tableName) .
            $tableName .
            $fields
        );
        $this->hashsCount++;
        return substr($shaHash, 0, 9);
    }
}
