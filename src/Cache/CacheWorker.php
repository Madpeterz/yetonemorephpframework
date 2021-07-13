<?php

namespace YAPF\Cache;

abstract class CacheWorker extends CacheRequired
{
    protected array $tablesConfig = [];
    protected string $accountHash = "None";
    protected array $tableLastChanged = [];
    protected bool $lastChangedUpdated = false;
    protected string $pathStarting = "";
    protected string $splitter = "-";
    protected array $changedTables = []; // tables found in this array will allways fail cache checks
    protected int $removed_counters = 0;

    protected function removeKey($key): void
    {
        $this->deleteKey($key . ".dat");
        $this->deleteKey($key . ".inf");
        $this->removed_counters++;
    }

    protected function saveLastChanged(): void
    {
        if ($this->lastChangedUpdated == true) {
            $this->writeKey($this->getLastChangedPath(), json_encode($this->tableLastChanged), "lastChanged", true);
        }
    }

    private function getLastChangedPath(): string
    {
        return $this->getWorkerPath() . "tables-lastchanged.inf";
    }

    private function getWorkerPath(): string
    {
        $path = "";
        if ($this->pathStarting != "") {
            $path = $this->pathStarting;
            $path .= $this->splitter;
        }
        return $path;
    }

    protected function loadLastChanged(): void
    {
        $path = $this->getLastChangedPath();
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
        $this->lastChangedUpdated = false;
    }

    protected function intLastChanged(): void
    {
        $this->lastChangedUpdated = true;
        foreach (array_keys($this->tablesConfig) as $table) {
            $this->tableLastChanged[$table] = time();
        }
    }

    /**
     * getHashInfo
     * returns the hash info dataset if found or a empty array
     * @return mixed[] ["unixtime" => int, "expires" => int, "allowChanged" => bool]
    */
    protected function getHashInfo(string $tableName, string $hash): array
    {
        return $this->getKeyInfo($this->getkeyPath($tableName, $hash));
    }

    /**
     * getKeyInfo
     * returns the key info dataset if found or a empty array
     * @return mixed[] ["unixtime" => int, "expires" => int, "allowChanged" => bool]
    */
    protected function getKeyInfo(string $key): array
    {
        if ($this->hasKey($key . ".inf") == false) {
            return []; // cache missing info dataset
        }
        $cacheInfoRead = $this->readKey($key . ".inf");
        return json_decode($cacheInfoRead, true);
    }

    protected function purgeHash(string $tableName, string $hash): bool
    {
        if (array_key_exists($tableName, $this->tablesConfig) == false) {
            return false;
        }
        $path = $this->getkeyPath($tableName, $hash);
        $check1 = $this->deleteKey($path . ".dat");
        $check2 = $this->deleteKey($path . ".inf");
        if ($check1 != $check2) {
            return false;
        }
        return $check1;
    }

    protected function getkeyPath(string $tableName, string $hash): string
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
        return $path;
    }
}
