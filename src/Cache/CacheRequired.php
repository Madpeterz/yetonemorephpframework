<?php

namespace YAPF\Framework\Cache;

use YAPF\Framework\Helpers\FunctionHelper;

abstract class CacheRequired extends FunctionHelper
{
    protected bool $connected = false; // set to true when a read/write passes ok

    protected function markConnected(): void
    {
        if ($this->connected == false) {
            $this->addError("Marking connected");
            $this->connected = true; // mark redis as connected
        }
    }

    abstract protected function setupCache(): bool;

    abstract protected function hasKey(string $key): bool;

    abstract protected function writeKeyReal(string $key, string $data, int $expiresUnixtime): bool;

    abstract protected function readKey(string $key): ?string;

    abstract protected function deleteKey(string $key): bool;

    public function getKey(string $key): ?string
    {
        return $this->readKey($key);
    }

    public function setKey(string $key, string $value, int $expiresUnixtime): bool
    {
        return $this->writeKeyReal($key, $value, $expiresUnixtime);
    }
}
