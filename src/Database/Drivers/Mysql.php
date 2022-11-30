<?php

namespace YAPF\Framework\Database\Drivers;

use mysqli;
use Throwable;
use YAPF\Framework\Database\QueryBuilder\SelectBuilder;
use YAPF\Framework\Responses\MySQLi\SelectReply;

class Mysql extends Sql
{
    protected ?mysqli $sqlConnection = null;

    protected function connect(): null|object
    {
        $this->stop();
        $this->sqlConnection = new mysqli(
            $this->dbHost,
            $this->dbUser,
            $this->dbPass,
            $this->dbName,
            $this->dbPort,
            $this->dbSocket
        );
    }

    protected function connect(): null|object
    {
        return null;
    }

    protected function disConnect(): void
    {
        $this->sqlConnection = null;
    }

    protected function save(): bool
    {
        return false;
    }

    public function select(SelectBuilder $query): SelectReply
    {
        if ($this->start() == false) {
            return new SelectReply($this->myLastErrorBasic);
        }
        $sql = $query->asSql();
        if ($sql == null) {
            return new SelectReply($query->getLastErrorBasic());
        }
        $sql = strtr($sql, ["  " => " "]);
        $sql = trim($sql);
        $stmt = null;
        try {
            $stmt = $this->sqlConnection->prepare($sql);
        } catch (Throwable $e) {
            $this->addError("Unable to prepare: " . $e->getMessage());
            return null;
        }
        if ($stmt == false) {
            $this->addError("Unable to prepare: " . $this->sqlConnection->error);
            return null;
        }

        $result = false;
        try {
            $result = $stmt->get_result();
        } catch (Throwable $e) {
            $stmt->close();
            $this->addError("statement failed due to error: " . $e);
            return new SelectReply($this->myLastErrorBasic);
        }
        $stmt->free_result();
        $stmt->close();
        return new SelectReply("not ready");
    }
}
