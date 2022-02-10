<?php

namespace YAPF\Config;

use YAPF\Cache\Cache;
use YAPF\Cache\Drivers\Disk;
use YAPF\Cache\Drivers\Redis;
use YAPF\Core\ErrorControl\ErrorLogging;
use YAPF\MySQLi\MysqliEnabled;

class SimpleConfig extends ErrorLogging
{
    // Cache
    protected ?Cache $Cache = null;
    protected bool $cache_enabled = false;

    // Cache / Disk
    protected bool $use_disk_cache = false;
    protected string $disk_cache_folder = "cache";

    // Cache / Redis
    protected bool $use_redis_cache = false;

    // Cache / Redis / Unix socket
    protected bool $redisUnix = false;
    protected string $redis_socket = "/var/run/redis/redis.sock";

    // Cache / Redis / TCP
    protected string $redis_host = "redis";
    protected int $redis_port = 6379;
    protected int $redis_timeout = 3;

    // docker flag
    protected bool $dockerConfigLocked = false;

    // SQL connection
    protected ?MysqliEnabled $sql = null;
    protected bool $systemDown = false;

    public function __construct()
    {
        if (class_exists("App\\Db", true) == false) {
            $offline = [
                "status" => 0,
                "message" => "- Service offline -<br/> DB config missing",
            ];
            die(json_encode($offline));
        }
    }

    public function shutdown(): void
    {
        $this->sql = null;
        $this->systemDown = true;
    }

    /*
        SQL functions
    */
    public function &getSQL(): ?MysqliEnabled
    {
        if ($this->systemDown == false) {
            if ($this->sql == null) {
                $this->sql = new MysqliEnabled();
            }
        }
        return $this->sql;
    }

    /*
        Cache functions
    */
    public function &getCacheDriver(): ?Cache
    {
        return $this->Cache;
    }

    public function configCacheDisabled(): void
    {
        if ($this->dockerConfigLocked == true) {
            return;
        }
        $this->use_redis_cache = false;
        $this->use_disk_cache = false;
    }
    public function configCacheRedisUnixSocket(string $socket = "/var/run/redis/redis.sock"): void
    {
        if ($this->dockerConfigLocked == true) {
            return;
        }
        $this->use_redis_cache = true;
        $this->redisUnix = true;
        $this->redis_socket = $socket;
    }
    public function configCacheRedisTCP(string $host = "redis", int $port = 6379, int $timeout = 3): void
    {
        if ($this->dockerConfigLocked == true) {
            return;
        }
        $this->use_redis_cache = true;
        $this->redisUnix = false;
        $this->redis_host = $host;
        $this->redis_port = $port;
        $this->redis_timeout = $timeout;
    }
    public function configCacheDisk(string $folder = "cache"): void
    {
        if ($this->dockerConfigLocked == true) {
            return;
        }
        $this->use_disk_cache = true;
        $this->disk_cache_folder = $folder;
    }

    public function setupCache(): void
    {
        $this->Cache = null;
        if ($this->use_redis_cache == true) {
            $this->startRedisCache();
        } elseif ($this->use_disk_cache == true) {
            $this->startDiskCache();
        }
        return;
    }

    public function startCache(): void
    {
        if ($this->use_redis_cache == true) {
            $this->Cache->start(false);
        } elseif ($this->use_disk_cache == true) {
            $this->Cache->start(true);
        }
        return;
    }

    protected function startRedisCache(): void
    {
        $this->Cache = new Redis();
        if ($this->redisUnix == true) {
            $this->Cache->connectUnix($this->redis_socket);
            return;
        }
        $this->Cache->setTimeout($this->redis_timeout);
        $this->Cache->connectTCP($this->redis_host, $this->redis_port);
    }

    protected function startDiskCache(): void
    {
        $this->Cache = new Disk($this->disk_cache_folder);
    }
}
