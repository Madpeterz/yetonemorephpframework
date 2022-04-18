<?php

namespace YAPF\Framework\Config;

use YAPF\Framework\Cache\Cache;
use YAPF\Framework\Cache\Drivers\Redis;
use YAPF\Core\ErrorControl\ErrorLogging;
use YAPF\Framework\MySQLi\MysqliEnabled;

class SimpleConfig extends ErrorLogging
{
    // Cache
    protected ?Cache $Cache = null;
    protected bool $cacheEnabled = false;

    // Cache / Redis
    protected bool $redisCache = false;

    // Cache / Redis / Unix socket
    protected bool $redisUnix = false;
    protected string $redisSocket = "/var/run/redis/redis.sock";

    // Cache / Redis / TCP
    protected string $redisHost = "redis";
    protected int $redisPort = 6379;
    protected int $redisTimeout = 1;

    // docker flag
    protected bool $usingDocker = false;

    // SQL connection
    protected ?MysqliEnabled $sql = null;

    public function __construct()
    {
        mysqli_report(MYSQLI_REPORT_OFF);
        if (class_exists("App\\Db", true) == false) {
            $offline = [
                "status" => 0,
                "message" => "- Service offline -<br/> DB config missing",
            ];
            die(json_encode($offline));
        }
    }

    public function __destruct()
    {
        $this->shutdown();
    }

    public function shutdown(): void
    {
        if ($this->sql != null) {
            $this->sql->shutdown();
            $this->sql = null;
        }
        if ($this->Cache != null) {
            $this->Cache->shutdown();
            $this->Cache = null;
        }
        $this->enableRestart = false;
    }

    protected bool $enableRestart = true;
    /*
        SQL functions
    */
    public function &getSQL(): ?MysqliEnabled
    {
        if (($this->sql == null) && ($this->enableRestart == true)) {
            $this->sql = new MysqliEnabled();
        }
        return $this->sql;
    }

    /*
        Cache functions
    */
    public function &getCacheDriver(): ?Cache
    {
        if (($this->Cache == null) && ($this->enableRestart == true)) {
            $this->setupCache();
            if ($this->Cache != null) {
                $this->startCache();
            }
        }
        return $this->Cache;
    }

    public function configCacheDisabled(): void
    {
        if ($this->usingDocker == true) {
            return;
        }
        $this->redisCache = false;
    }
    public function configCacheRedisUnixSocket(string $socket = "/var/run/redis/redis.sock"): void
    {
        if ($this->usingDocker == true) {
            return;
        }
        $this->redisCache = true;
        $this->redisUnix = true;
        $this->redisSocket = $socket;
    }
    public function configCacheRedisTCP(string $host = "redis", int $port = 6379, int $timeout = 3): void
    {
        if ($this->usingDocker == true) {
            return;
        }
        $this->redisCache = true;
        $this->redisUnix = false;
        $this->redisHost = $host;
        $this->redisPort = $port;
        $this->redisTimeout = $timeout;
    }

    public function setupCache(): void
    {
        $this->Cache = null;
        if ($this->redisCache == true) {
            $this->startRedisCache();
        }
        return;
    }

    /*
        Tables to enable with cache
    */
    protected function setupCacheTables(): void
    {
    }

    public function startCache(): void
    {
        $this->setupCacheTables();
        if ($this->redisCache == true) {
            $this->Cache->start(false);
        }
        return;
    }

    protected function startRedisCache(): void
    {
        $this->Cache = new Redis();
        if ($this->redisUnix == true) {
            $this->Cache->connectUnix($this->redisSocket);
            return;
        }
        $this->Cache->setTimeout($this->redisTimeout);
        $this->Cache->connectTCP($this->redisHost, $this->redisPort);
    }
}
