<?php

namespace YAPF\Cache;

class DiskCache extends Cache implements CacheInterface
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

    /**
     * getKeys
     * returns an array of strings of keys for the cache
     * @return string[]
    */
    protected function getKeys(): array
    {
        return $this->mapKeysInFolder($this->pathStarting);
    }

    /**
     * mapKeysInFolder
     * helper function for getKeys for DiskCache only
     * @return string[]
    */
    private function mapKeysInFolder(string $folder): array
    {
        $results = [];
        $scan = scandir($folder);
        $working_path = $folder . "/";
        foreach ($scan as $file) {
            if ($file == "..") {
                continue;
            } elseif ($file == ".") {
                continue;
            }
            if (is_dir($working_path . $file) == true) {
                 $results = array_merge($this->mapKeysInFolder($working_path . $file), $results);
            }
            $ending = substr($file, -4);
            if ($ending == ".dat") {
                $filepart = explode(".", $file);
                $results[] = $working_path . $filepart[0];
            }
        }
        return $results;
    }
}
