<?php

namespace YAPF\Cache;

interface CacheInterface
{
    public function __destruct();

    public function start(bool $selfCleanup = false): void;

    public function cleanup(int $max_counter = 5): void;

    public function shutdown(): void;

    public function setAccountHash(string $acHash): void;

    public function addTableToCache(string $tablename, int $autoExpireMins = 15, bool $sharedDataset = false): void;

    public function markChangeToTable(string $tableName): void;

    public function cacheVaild(string $tableName, string $hash): bool;

    /**
     * readHash
     * attempts to read the cache for the selected mapping.
     * @return mixed[] [id => [key => value,...], ...]
    */
    public function readHash(string $tableName, string $hash): array;
}
