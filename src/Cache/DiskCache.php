<?php

namespace YAPF\Cache;

class DiskCache extends Cache implements CacheInterface
{
    protected $tempStorage = [];
    // writes cache to mem first, and then to disk at the end
    // saves unneeded writes if we make a change after loading.

    public function __construct(
        string $cacheFolder = "cacheTmp"
    ) {
        $this->splitter = "/";
        $this->pathStarting = $cacheFolder;
    }

    public function shutdown(): void
    {
        parent::shutdown();
        $this->finalizeWrites();
    }

    protected function finalizeWrites(): void
    {
        foreach ($this->tempStorage as $tmpKey => $dataset) {
            /*
            "key" => $key,
            "data" => $data,
            "table" => $table,
            "versionID" => time(),
            */
            if ($dataset["versionID"] != $this->tableLastChanged[$dataset["table"]]) {
                continue; // skipped write, table changed from read
            }
            $this->writeKey($dataset["key"], $dataset["data"], $dataset["table"], true);
        }
        $this->tempStorage = [];
    }

    protected function setupCache(): void
    {
        $this->addErrorlog("Cache folder:" . $this->pathStarting);
        if (is_dir($this->pathStarting) == false) {
            mkdir($this->pathStarting);
        }
    }

    protected function hasKey(string $key): bool
    {
        $this->addErrorlog("Checking cache file: " . $key);
        return file_exists($key);
    }

    protected function deleteKey(string $key): bool
    {
        if (file_exists($key) == true) {
            return unlink($key);
        }
        return true;
    }

    protected function writeKey(string $key, string $data, string $table, bool $finalWrite = false): bool
    {
        if ($finalWrite == true) {
            if ($this->deleteKey($key) == false) {
                return false;
            }
            $bits = explode("/", $key);
            array_pop($bits);
            $ubit = "";
            $addon = "";
            foreach ($bits as $bit) {
                $ubit .= $addon;
                $ubit .= $bit;
                if (is_dir($ubit) == false) {
                    mkdir($ubit);
                }
                $addon = "/";
            }
            $this->addErrorlog("Writing cache file: " . $key);
            $writeFile = file_put_contents($key, $data);
            if ($writeFile === false) {
                return false;
            }
            return true;
        }
        $tempKey = substr(sha1($key), 0, 6);
        $this->tempStorage[$tempKey] = [
            "key" => $key,
            "data" => $data,
            "table" => $table,
            "versionID" => $this->tableLastChanged[$table],
        ];
        $this->addErrorlog("Putting " . $key . " onto temp");
        return true;
    }

    protected function readKey(string $key): string
    {
        $this->addErrorlog("readKey: " . $key);
        return file_get_contents($key);
    }

    public function purge(): bool
    {
        $keys = $this->getKeys();
        foreach ($keys as $key) {
            $this->removeKey($key);
        }
        $this->cleanFolders($this->pathStarting);
        return true;
    }

    protected function cleanFolders($folder): void
    {
        $scan = scandir($folder);
        $working_path = $folder . "/";
        $folder_busy = false;
        foreach ($scan as $file) {
            if ($file == "..") {
                continue;
            } elseif ($file == ".") {
                continue;
            }
            if (is_dir($working_path . $file) == true) {
                $this->cleanFolders($working_path . $file);
                continue;
            }
            $folder_busy = true;
        }
        if ($folder_busy == false) {
            rmdir($folder);
        }
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
