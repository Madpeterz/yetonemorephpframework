<?php

namespace YAPF\Framework\Cache\Drivers;

use Predis\Client as RedisClient;
use Predis\Connection\ConnectionException;
use Throwable;
use YAPF\Framework\Cache\Cache;
use YAPF\Framework\Cache\CacheInterface;

class Redis extends Cache implements CacheInterface
{
    protected string $driverName = "Redis";
    protected $tempStorage = [];
    // writes cache to mem first, and then to disk at the end
    // saves unneeded writes if we make a change after loading.
    protected ?RedisClient $client;
    protected int $serverTimeout = 2;
    protected array $connectionSettings = [];
    protected bool $enabled = false;

    public function setTimeout(int $newTimeoutValue = 4): void
    {
        $this->serverTimeout = $newTimeoutValue;
    }

    public function shutdown(): void
    {
        parent::shutdown();
        if ($this->enabled == true) {
            $this->client->disconnect();
        }
    }


    public function connectUnix(string $unixSocket): bool
    {
        $this->setConnectionSettings([
            'scheme' => 'unix',
            'path' => $unixSocket,
            'timeout' => $this->serverTimeout,
            'read_write_timeout' => $this->serverTimeout,
        ]);
        return $this->connectNow();
    }

    public function connectTCP(string $serverIP, int $serverPort = 6379): bool
    {
        $this->setConnectionSettings([
            'scheme' => 'tcp',
            'host'   => $serverIP,
            'port'   => $serverPort,
            'timeout' => $this->serverTimeout,
            'read_write_timeout' => $this->serverTimeout,
        ]);
        return $this->connectNow();
    }

    public function setConnectionSettings(array $settings): void
    {
        $this->connectionSettings = $settings;
    }

    protected function connectNow(): bool
    {
        try {
            $this->client = new RedisClient($this->connectionSettings);
            $this->client->connect();
            $this->enabled = true;
            $this->client->pipeline();
            return true;
        } catch (Throwable $ex) {
            $this->addError("Marking cache as disconnected (failed to connect) " . $ex->getMessage());
            $this->disconnected = true;
            $this->enabled = false;
            return false;
        }
    }

    protected function setupCache(): bool
    {
        $this->addError("Cache server: Redis - Please do not mess it up");
        return true;
    }

    protected function hasKey(string $key): bool
    {
        if ($this->enabled == false) {
            return false;
        }
        if (in_array($key, $this->seenKeys) == true) {
            return true;
        }
        try {
            if ($this->client->exists($key) == 1) {
                $this->seenKeys[] = $key;
                return true;
            }
        } catch (Throwable $ex) {
            $this->addError("hasKey error: " . $ex->getMessage());
        }
        return false;
    }

    protected function deleteKey(string $key): bool
    {
        if ($this->disconnected == true) {
            return false;
        }
        if ($this->enabled == false) {
            $this->addError("[deleteKey] Skipped redis is not connected");
            return false;
        }
        if ($this->hasKey($key) == false) {
            $this->addError("[deleteKey] Skipped " . $key . " its not found");
            return true;
        }
        if (in_array($key, $this->seenKeys) == true) {
            unset($this->seenKeys[$key]);
        }
        try {
            if ($this->client->del($key) == 1) {
                $this->addError("[deleteKey] Removed key " . $key . " from server");
                return true;
            }
            $this->addError("[deleteKey] failed to remove " . $key . " from server");
        } catch (Throwable $ex) {
            $this->disconnected = true;
            $this->addError("Marking cache as disconnected (failed to delete key) " . $ex->getMessage());
        }
        return false;
    }

    protected function writeKeyReal(string $key, string $data, int $expiresUnixtime): bool
    {
        if ($this->disconnected == true) {
            $this->addError("writeKeyReal: redis is marked is gone");
            return false;
        }
        if ($this->enabled == false) {
            $this->addError("writeKeyReal: error redis not connected");
            return false;
        }
        try {
            $reply = $this->client->setex($key, $expiresUnixtime - time(), $data);
            $this->addError("writeKeyReal: " . $reply . " for " . $key);
            $this->markConnected();
            return true;
        } catch (Throwable $ex) {
            $this->addError("Marking cache as disconnected (failed to write key) " .
            $ex->getMessage() . " Details\n Key: " . $key . " Data: " . $data);
            $this->disconnected = true;
        }
        return false;
    }

    protected function readKey(string $key): ?string
    {
        if ($this->disconnected == true) {
            return false;
        }
        if ($this->enabled == false) {
            return null;
        }
        try {
            return $this->client->get($key);
        } catch (Throwable $ex) {
            $this->addError("Marking cache as disconnected (failed to read key) " . $ex->getMessage());
            $this->disconnected = true;
        }
        return null;
    }

    public function purge($attempts = 0): bool
    {
        if ($this->enabled == false) {
            $this->addError("Redis unable to purge its not connected");
            return false;
        }
        $keys = $this->getKeys();
        if ($keys == null) {
            $this->addError("Redis unable to get keys");
            return false;
        }
        if ((count($keys) == 0) || ($attempts > 3)) {
            $this->addError("Redis should be clean now");
            return true;
        }
        foreach ($keys as $key) {
            $this->deleteKey($key);
        }
        sleep(1);
        return $this->purge($attempts + 1);
    }

    /**
     * getKeys
     * returns null on failed, otherwise an array of keys
     * @return mixed[]
     */
    public function getKeys(): ?array
    {
        if ($this->enabled == false) {
            return null;
        }
        try {
            $reply = $this->client->keys("*");
            if ($reply != null) {
                $this->seenKeys = $reply;
            }
            return $reply;
        } catch (Throwable $ex) {
            $this->addError("readKey error: " . $ex->getMessage());
        }
        return [];
    }
}
