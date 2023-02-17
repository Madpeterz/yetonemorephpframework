<?php

namespace YAPF\Framework\Cache\Drivers\Redis;

use Exception;
use Predis\Client as RedisClient;
use Throwable;
use YAPF\Framework\Cache\Drivers\Framework\CacheDriver;
use YAPF\Framework\Responses\Cache\CacheStatusReply;

abstract class Core extends CacheDriver
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

    public function stop(): bool
    {
        if ($this->disconnected == false) {
            if ($this->client == null) {
                $this->addError("No client to disconnect!");
                return false;
            }
            try {
                $this->client->disconnect();
            } catch (Exception $e) {
                $this->addError($e->getMessage());
                return false;
            }
        }
        $this->disconnected = true;
        return true;
    }
}
