<?php

namespace YAPF\Framework\Cache;

use YAPF\Framework\Helpers\FunctionHelper;

abstract class CacheDatastore extends FunctionHelper
{
    // self store keys loaded in memory
    protected array $keys = [];
    protected array $pendingWriteKeys = [];
    protected array $pendingDeleteKeys = [];

    public function seenKey(string $key): bool
    {
        return array_key_exists($key, $this->keys);
    }

    public function loadKey(string $key, string $value): void
    {
        $this->keys[$key] = $value;
    }

    public function removeKey(string $key): bool
    {
        if ($this->seenKey($key) == false) {
            return false;
        }
        unset($this->keys[$key]);
        return true;
    }
}
