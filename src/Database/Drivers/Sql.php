<?php

namespace YAPF\Framework\Database\Drivers;

use YAPF\Core\ErrorControl\ErrorLogging;

abstract class Sql extends ErrorLogging
{
    protected $sqlConnection = null;
    protected ?string $dbName = null;
    protected ?string $dbUser = null;
    protected ?string $dbPass = null;
    protected ?string $dbHost = null;
    protected ?int $dbPort = null;
    protected ?string $dbSocket = null;
    public function __construct(
        ?string $database = null,
        ?string $username = null,
        ?string $password = null,
        ?string $host = null,
        ?int $port = null,
        ?string $socket = null
    ) {
        $this->dbName = $database;
        $this->dbUser = $username;
        $this->dbPass = $password;
        $this->dbHost = $host;
        $this->dbPort = $port;
        $this->dbSocket = $socket;
    }

    protected function stop(): bool
    {
        if ($this->sqlConnection == null) {
            return true;
        }
        if ($this->save() == false) {
            return false;
        }
        $this->disConnect();
        return false;
    }

    protected function disConnect(): void
    {
        $this->sqlConnection = null;
    }

    protected function save(): bool
    {
        return false;
    }

    protected function start(): bool
    {
        if ($this->sqlConnection != null) {
            return true; // connection is already open!
        }
        if ($this->hasDbConfig() == false) {
            $error_msg = "DB config is not valid to start!";
            $this->addError($error_msg);
            return false;
        }
        $this->sqlConnection = $this->connect();
        if ($this->sqlConnection == null) {
            return false;
        }
        return true;
    }

    protected function connect(): null|object
    {
        return null;
    }

    protected function hasDbConfig(): bool
    {
        $checks = [$this->dbName, $this->dbHost, $this->dbPass, $this->dbUser];
        if (in_array(null, $checks) == true) {
            $this->addError("DB config is not vaild:" . json_encode([
                "host" => $this->dbHost,
                "db" => $this->dbName,
                "user" => $this->dbUser]));
            return false;
        }
        $disallowed_users = ["[DB_USER]", "", " "];
        return !in_array($this->dbUser, $disallowed_users);
    }
}
