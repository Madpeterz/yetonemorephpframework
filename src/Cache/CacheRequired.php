<?php

namespace YAPF\Cache;

abstract class CacheRequired
{
    protected function setupCache(): void
    {
    }

    protected function hasKey(string $key): bool
    {
        return false;
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
    /**
     * getKeys
     * returns an array of strings of keys for the cache
     * @return string[]
    */
    protected function getKeys(): array
    {
        return [];
    }
}
