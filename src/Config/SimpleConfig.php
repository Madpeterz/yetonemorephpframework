<?php

namespace YAPF\Framework\Config;

use ErrorException;
use YAPF\Core\ErrorControl\ErrorLogging;
use YAPF\Framework\MySQLi\MysqliEnabled;

abstract class SimpleConfig extends ErrorLogging
{
    // Cache
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
    protected bool $allowChanges = false;

    public function setAllowChanges(bool $status): void
    {
        $this->allowChanges = $status;
    }

    public function getAllowDbWrites(): bool
    {
        return $this->allowChanges;
    }

    public function __construct()
    {
        if (class_exists("App\\Db", true) == false) {
            $offline = [
                "status" => 0,
                "message" => "- Service offline -<br/> DB config missing",
            ];
            throw new ErrorException(json_encode($offline), 911, 911);
        }
        mysqli_report(MYSQLI_REPORT_OFF);
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
        $this->enableRestart = false;
    }

    protected bool $enableRestart = true;
    /*
        SQL functions
    */
    public function &getSQL(): ?MysqliEnabled
    {
        if (($this->sql == null) && ($this->enableRestart == true)) {
            $this->addError("Starting SQL service");
            $this->sql = new MysqliEnabled();
        }
        return $this->sql;
    }
}
