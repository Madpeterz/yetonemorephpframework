<?php

namespace YAPF\Cache;

abstract class Cache
{
    protected array $tablesConfig = [];
    protected string $accountHash = "None";
    protected array $tableLastChanged = [];
    protected bool $lastChangedUpdated = false;
    protected string $pathStarting = "";
    protected string $splitter = "-";

    public function start(): void
    {
        $this->setupCache();
        $this->intLastChanged();
        $this->loadLastChanged();
    }

    public function setAccountHash(string $acHash): void
    {
        if (strlen($acHash) > 5) {
            $this->accountHash = substr($acHash, 0, 5);
            return;
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

    public function markChangeToTable(string $tablename): void
    {
        $this->tableLastChanged[$tablename] = time();
        $this->lastChangedUpdated = true;
    }

    protected function loadLastChanged(): void
    {
        $path = "";
        if ($this->pathStarting != "") {
            $path = $this->pathStarting;
            $path .= $this->splitter;
        }
        $path .= "tables-lastchanged.inf";
        if ($this->hasKey($path) == false) {
            return;
        }
        $cacheInfoRead = $this->readKey($path);
        $info_file = json_decode($cacheInfoRead, true);
        $dif = time() - $info_file["updatedUnixtime"];
        if ($dif > (60 * 60)) {
            // info dataset is to old to be used
            // everything is marked as changed right now
            return;
        }
        foreach (array_keys($this->tablesConfig) as $table) {
            if (array_key_exists($table, $info_file) == true) {
                $this->tableLastChanged[$table] = $info_file[$table];
            }
        }
    }

    private function intLastChanged(): void
    {
        foreach (array_keys($this->tablesConfig) as $table) {
            $this->tableLastChanged[$table] = time();
        }
    }

    protected function setupCache(): void
    {
    }

    protected function hasKey(string $key): bool
    {
        return false;
    }
    public function cacheVaild(string $tableName, string $hash): bool
    {
        if (array_key_exists($tableName, $this->tablesConfig) == false) {
            return false;
        }
        if (array_key_exists($tableName, $this->tableLastChanged) == false) {
            return false;
        }
        $use_account_hash = $this->accountHash;
        if ($this->tablesConfig[$tableName]["shared"] == true) {
            $use_account_hash = "None";
        }
        $path = "";
        if ($this->pathStarting != "") {
            $path = $this->pathStarting;
            $path .= $this->splitter;
        }
        $path .= $tableName;
        $path .= $this->splitter;
        $path .= $use_account_hash;
        $path .= $this->splitter;
        $path .= $hash;
        if ($this->hasKey($path . ".inf") == false) {
            return false; // cache missing info dataset
        }
        $cacheInfoRead = $this->readKey($path . ".inf");
        $info_file = json_decode($cacheInfoRead, true);
        if ($info_file["expires"] < time()) {
            return false; // cache has expired
        }
        if ($info_file["unixtime"] < $this->tableLastChanged[$tableName]) {
            return !$info_file["allowChanged"]; // cache is old (this is fine is allowChanged is true)
        }
        return true; // cache is vaild
    }
    protected function purgeHash(string $tableName, string $hash): bool
    {
        if (array_key_exists($tableName, $this->tablesConfig) == false) {
            return false;
        }
        $use_account_hash = $this->accountHash;
        if ($this->tablesConfig[$tableName]["shared"] == true) {
            $use_account_hash = "None";
        }
        $path = "";
        if ($this->pathStarting != "") {
            $path = $this->pathStarting;
            $path .= $this->splitter;
        }
        $path .= $tableName;
        $path .= $this->splitter;
        $path .= $use_account_hash;
        $path .= $this->splitter;
        $path .= $hash;
        $check1 = $this->deleteKey($path . ".dat");
        $check2 = $this->deleteKey($path . ".inf");
        if ($check1 != $check2) {
            return false;
        }
        return $check1;
    }

    public function writeHash(string $tableName, string $hash, array $data, bool $allowChanged): bool
    {
        if (array_key_exists($tableName, $this->tablesConfig) == false) {
            return false;
        }
        $use_account_hash = $this->accountHash;
        if ($this->tablesConfig[$tableName]["shared"] == true) {
            $use_account_hash = "None";
        }
        $path = "";
        if ($this->pathStarting != "") {
            $path = $this->pathStarting;
            $path .= $this->splitter;
        }
        $path .= $tableName;
        $path .= $this->splitter;
        $path .= $use_account_hash;
        $path .= $this->splitter;
        $path .= $hash;
        $info_file = [
            "unixtime" => time(),
            "expires" => time() + (60 * $this->tablesConfig["autoExpire"]),
            "allowChanged" => $allowChanged,
        ];
        $writeOne = $this->writeKey($path . "inf", json_encode($info_file));
        $writeTwo = $this->writeKey($path . "data", json_encode($data));
        if ($writeOne != $writeTwo) {
            $this->purgeHash($tableName, $hash);
            return false;
        }
        return $writeOne;
    }
    /**
     * readHash
     * attempts to read the cache for the selected mapping.
     * @return mixed[] [id => [key => value,...], ...]
    */
    public function readHash(string $tableName, string $hash): array
    {
        $use_account_hash = $this->accountHash;
        if ($this->tablesConfig[$tableName]["shared"] == true) {
            $use_account_hash = "None";
        }
        $path = "";
        if ($this->pathStarting != "") {
            $path = $this->pathStarting;
            $path .= $this->splitter;
        }
        $path .= $tableName;
        $path .= $this->splitter;
        $path .= $use_account_hash;
        $path .= $this->splitter;
        $path .= $hash;
        $readBlob = $this->readKey($path . ".dat");
        return json_decode($readBlob, true);
    }

    protected function writeKey(string $key, string $data): bool
    {
        return false;
    }

    protected function readKey(string $key): string
    {
        return "";
    }

    protected function deleteKey(string $key): bool
    {
        return false;
    }
}
