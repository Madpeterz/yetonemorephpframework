<?php

namespace YAPF\Catche;

class Cache extends CacheClient
{
    protected array $singleCache = []; // table => expirySecs
    protected array $setCache = []; // table => expirySecs
    protected array $tableVersions = []; // table => versionNumber, -1 means no version loaded
    protected array $readKeys = [];
    protected bool $attemptedGetVersions = false;
    protected array $pendingChanges = []; // table => [key => value]
    protected array $pendingDeletes = []; // table => [key => value]
    /*
        key => array
            [
                "table" => string,
                "value" => string,
                "version" => int
                "expiry" => int,
            ]
    */

    public function __construct(
        protected string $appName = "myapp",
        protected bool $cacheEnabled = false,
        protected bool $usingSocket = false,
        protected string $redisSocket = "/var/run/redis/redis.sock",
        protected string $redisHost = "redis",
        protected int $redisPort = 6379,
        protected int $redisTimeout = 1,
    ) {
        parent::__construct($cacheEnabled, $usingSocket, $redisSocket, $redisHost, $redisPort, $redisTimeout);
    }

    public function savePendingChanges(): void
    {
        foreach ($this->pendingDeletes as $table => $keys) {
            $this->deleteKeys($keys);
            $this->nextVersion($table);
        }
        foreach ($this->pendingChanges as $table => $changes) {
            foreach ($changes as $key => $value) {
                $this->setKey($key, json_encode($value));
            }
        }
    }

    protected function getAppConfigName(): string
    {
        return $this->appName . ".config";
    }

    protected function getVersion(string $table): ?int
    {
        if ($this->attemptedGetVersions == false) {
            $this->attemptedGetVersions = true;
            $appConfig = $this->getFromCache($this->getAppConfigName(), $this->getAppConfigName(), ignoreMem:true);
            if ($appConfig != null) {
                try {
                    $json = json_decode($appConfig, true);
                    if (is_array($json) == false) {
                    }
                    if (array_key_exists("yapfcache", $json) == false) {
                    }
                    $this->tableVersions = $json["yapfcache"];
                } catch (\Throwable $e) {
                }
            }
            return -1;
        }
        if (array_key_exists($table, $this->tableVersions) == false) {
            $this->tableVersions[$table] = 1;
        }
        if ($this->tableVersions[$table] == -1) {
            $this->tableVersions[$table] = 1;
        }
        return $this->tableVersions[$table];
    }
    protected function nextVersion(string $table, bool $asSingle = false): void
    {
        if ($table == $this->getAppConfigName()) {
            return;
        }
        $this->getVersion($table); // make sure the version is loaded
        $process = array_key_exists($table, $this->setCache);
        if ($asSingle == true) {
            $process = array_key_exists($table, $this->singleCache);
        }
        if ($process == false) {
            return;
        }
        $this->tableVersions[$table]++;
        if ($this->tableVersions[$table] == 99999) {
            $this->tableVersions[$table] = 1;
        }
    }
    protected function getFromMem(string $key): ?string
    {
        if (array_key_exists($key, $this->readKeys) == false) {
            return null;
        }
        return $this->readKeys[$key]["value"];
    }
    public function setPendingChange(string $table, string $key, string $value): void
    {
        if (array_key_exists($table, $this->pendingChanges) == false) {
            $this->pendingChanges[$table] = [];
        }
        $this->pendingChanges[$table][$key] = $value;
    }
    public function getFromCache(
        string $table,
        string $key,
        bool $asSingle = false,
        bool $ignoreMem = false
    ): ?string {
        if ($table == $this->getAppConfigName()) {
            return $this->getKey($key);
        }
        $process = array_key_exists($table, $this->setCache);
        if ($asSingle == true) {
            $process = array_key_exists($table, $this->singleCache);
        }
        if ($process == false) {
            return null;
        }
        if ($ignoreMem == false) {
            $readMem = $this->getFromMem($key);
        }
        if ($readMem == null) {
            $readMem = $this->getKey($key);
        }
        if ($readMem == null) {
            return null;
        }
        $json = json_decode($readMem, true);
        if (is_array($json) == false) {
            return null; // json packet is not valid
        }
        $requiredFields = ['expiry', 'table', 'value', 'version'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $json)) {
                return null; // json packet is not valid after decoding
            }
        }
        if ($json['expiry'] < time()) {
            return null; // expired
        }
        if ($json['table'] !== $table || $json['version'] !== $this->getVersion($table)) {
            return null; // wrong table or version
        }
        $data = json_decode($json["value"], true);
        if (is_array($data) == false) {
            return null; // json packet is not valid after decoding
        }
        if ($ignoreMem == false) {
            // put into mem
            $this->readKeys[$key] = [
                "table" => $table,
                "value" => json_encode($json["value"]),
                "version" => $this->getVersion($table),
                "expiry" => $json["expiry"],
            ];
        }
        return $json["value"];
    }

    public function singleCacheTable(string $table, int $maxAgeSecs): void
    {
        if (array_key_exists($table, $this->tableVersions) == false) {
            $this->tableVersions[$table] = -1;
        }
        $this->singleCache[$table] = $maxAgeSecs;
    }
    public function setCacheTable(string $table, int $maxAgeSecs): void
    {
        if (array_key_exists($table, $this->tableVersions) == false) {
            $this->tableVersions[$table] = -1;
        }
        $this->setCache[$table] = $maxAgeSecs;
    }
}
