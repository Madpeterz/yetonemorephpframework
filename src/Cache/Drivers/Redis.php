<?php

namespace YAPF\Framework\Cache\Drivers;

use Predis\Client as RedisClient;
use Throwable;
use YAPF\Framework\Cache\Drivers\Framework\CacheDriver;
use YAPF\Framework\Cache\Drivers\Framework\CacheInterface;
use YAPF\Framework\Responses\Cache\CacheStatusReply;
use YAPF\Framework\Responses\Cache\DeleteReply;
use YAPF\Framework\Responses\Cache\ListKeysReply;
use YAPF\Framework\Responses\Cache\PurgeReply;
use YAPF\Framework\Responses\Cache\ReadReply;
use YAPF\Framework\Responses\Cache\WriteReply;

class Redis extends CacheDriver implements CacheInterface
{
    protected int $serverTimeout = 2;
    protected ?RedisClient $client;
    protected ?array $connectionSettings = null;

    public function __construct(?array $connectSettings = null)
    {
        if ($connectSettings != null) {
            $this->setConnectionSettings($connectSettings);
            $this->start();
        }
    }


    /**
     * It sets the connection settings to use a unix socket, and then starts the connection
     * @return CacheStatusReply A CacheStatusReply object.
     */
    public function connectUnix(string $unixSocket): CacheStatusReply
    {
        $this->setConnectionSettings([
            'scheme' => 'unix',
            'path' => $unixSocket,
            'timeout' => $this->serverTimeout,
            'read_write_timeout' => $this->serverTimeout,
        ]);
        return $this->start();
    }

    public function getKeyLength(): int
    {
        return 20;
    }

    /**
     * It sets the connection settings to use TCP, and then starts the connection
     * @return CacheStatusReply A CacheStatusReply object.
     */
    public function connectTCP(string $serverIP = "127.0.0.1", int $serverPort = 6379): CacheStatusReply
    {
        $this->setConnectionSettings([
            'scheme' => 'tcp',
            'host'   => $serverIP,
            'port'   => $serverPort,
            'timeout' => $this->serverTimeout,
            'read_write_timeout' => $this->serverTimeout,
        ]);
        return $this->start();
    }

    public function setConnectionSettings(array $settings): void
    {
        $this->connectionSettings = $settings;
    }

    public function driverName(): string
    {
        return "Predis";
    }

    public function setTimeout(int $timeout = 2): bool
    {
        if (($timeout < 1) || ($timeout > 5)) {
            return false;
        }
        $this->serverTimeout = $timeout;
        return true;
    }

    public function deleteKeys(array $keys): PurgeReply
    {
        if ($this->readyToTakeAction() == false) {
            return new PurgeReply($this->getLastErrorBasic());
        }
        if (count($keys) < 0) {
            return new PurgeReply("no keys given", 0, true);
        }
        $deleteOk = true;
        $deleteMessage = "All keys deleted";
        $deleteCount = 0;
        foreach ($keys as $key) {
            $reply = $this->deleteKey($key);
            if ($reply->status == false) {
                $deleteOk = false;
                $deleteMessage = $reply->message;
                break;
            }
            $deleteCount++;
        }
        return new PurgeReply($deleteMessage, $deleteCount, $deleteOk);
    }

    public function purgeAllKeys(): PurgeReply
    {
        if ($this->readyToTakeAction() == false) {
            return new PurgeReply($this->getLastErrorBasic());
        }
        $reply = $this->listKeys();
        if ($reply->status == false) {
            return new PurgeReply($reply->message);
        }
        return $this->deleteKeys($reply->keys);
    }

    public function readKey(string $key): ReadReply
    {
        if ($this->readyToTakeAction() == false) {
            return new ReadReply($this->getLastErrorBasic(), $key);
        }
        if (strlen($key) > 1000) {
            return new ReadReply("Invaild key length [max 1000]");
        }
        try {
            $value = $this->client->get($key);
            $this->keyReads++;
            return new ReadReply("ok", $value, true);
        } catch (Throwable $ex) {
            $this->addError("Failed to read key: " . $ex->getMessage());
            $this->disconnected = true;
        }
        return null;
    }

    public function writeKey(string $key, string $value, ?int $expireUnixtime = null): WriteReply
    {
        if ($this->readyToTakeAction() == false) {
            return new WriteReply($this->getLastErrorBasic());
        }
        if ($expireUnixtime < time()) {
            return new WriteReply("Invaild expire unixtime");
        }
        if (strlen($key) > 1000) {
            return new WriteReply("Invaild key length [max 1000]");
        }
        try {
            $this->client->setex($key, $expireUnixtime - time(), $value);
            $this->keyWrites++;
            return new WriteReply("ok", true);
        } catch (Throwable $ex) {
            $this->addError("failed to write key: " .
            $ex->getMessage() . " Key: " . $key);
            $this->disconnected = true;
        }
        return new WriteReply($this->getLastErrorBasic());
    }

    public function deleteKey(string $key): DeleteReply
    {
        if ($this->readyToTakeAction() == false) {
            return new DeleteReply($this->getLastErrorBasic());
        }
        if (strlen($key) > 1000) {
            return new DeleteReply("Invaild key length [max 1000]");
        }
        if ($this->hasKey($key) == false) {
            $this->keyDeletes++;
            return new DeleteReply("not found but thats fine", true);
        }
        try {
            if ($this->client->del($key) == 1) {
                $this->keyDeletes++;
                return new DeleteReply("ok", true);
            }
            $this->addError("Failed to remove " . $key . " from server");
        } catch (Throwable $ex) {
            $this->disconnected = true;
            $this->addError("failed to delete key: " . $ex->getMessage());
        }
        return new DeleteReply($this->getLastErrorBasic());
    }

    public function listKeys(): ListKeysReply
    {
        if ($this->readyToTakeAction() == false) {
            return new ListKeysReply($this->getLastErrorBasic());
        }
        try {
            $reply = $this->client->keys("*");
            if ($reply != null) {
                return new ListKeysReply("ok", $reply, true);
            }
            return new ListKeysReply("keys list is null", status:true);
        } catch (Throwable $ex) {
            $this->addError($ex->getMessage());
        }
        return new ListKeysReply($this->getLastErrorBasic(), status:true);
    }

    public function hasKey(string $key): CacheStatusReply
    {
        if ($this->readyToTakeAction() == false) {
            return new CacheStatusReply($this->getLastErrorBasic());
        }
        try {
            if ($this->client->exists($key) == 1) {
                return new CacheStatusReply("ok", true);
            }
        } catch (Throwable $ex) {
            $this->addError($ex->getMessage());
        }
        return new CacheStatusReply($this->getLastErrorBasic());
    }

    public function start(): CacheStatusReply
    {
        $this->disconnected = true;
        try {
            $this->client = new RedisClient($this->connectionSettings);
            $this->client->connect();
            $this->client->pipeline();
            $this->disconnected = false;
            return new CacheStatusReply("predis client started", true);
        } catch (Throwable $ex) {
            $this->addError("Failed to connect: " . $ex->getMessage());
        }
        return new CacheStatusReply($ex->getMessage());
    }

    public function stop(): void
    {
        if ($this->disconnected == false) {
            $this->client->disconnect();
        }
        $this->disconnected = true;
    }
}
