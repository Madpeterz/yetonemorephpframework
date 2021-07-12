<?php

namespace YAPF\Cache;

class DiskCache extends Cache
{
    public function __construct(
        string $cacheFolder = "cacheTmp"
    ) {
        $this->splitter = "/";
        $this->pathStarting = $cacheFolder;
    }

    protected function setupCache(): void
    {
        if (is_dir($this->pathStarting) == false) {
            mkdir($this->pathStarting);
        }
    }

    protected function hasKey(string $key): bool
    {
        return file_exists($key);
    }

    protected function deleteKey(string $key): bool
    {
        if (file_exists($key) == true) {
            return unlink($key);
        }
        return true;
    }

    protected function writeKey(string $key, string $data): bool
    {
        $this->deleteKey($key);
        $writeFile = file_put_contents($key, $data);
        if ($writeFile === false) {
            return false;
        }
        return true;
    }

    protected function readKey(string $key): string
    {
        return file_get_contents($key);
    }
}
