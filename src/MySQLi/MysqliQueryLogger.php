<?php

namespace YAPF\Framework\MySQLi;

use App\Db as Db;

abstract class MysqliQueryLogger extends Db
{
    protected bool $logQueries = false;
    protected array $queryLog = [];
    /**
     * @return array<string, array{count: int, queries: array<int, array{sql: string, time: float}>, rows: int}>
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }
    protected function logQuery(string $type, string $query, int $rowsAffected, float $timetaken): void
    {
        if ($this->logQueries === false) {
            return;
        }
        if (array_key_exists($type, $this->queryLog) === false) {
            $this->addError("Invalid query type for logging: " . $type);
            return;
        }

        $this->queryLog[$type]["count"]++;
        $this->queryLog[$type]["queries"][] =
        [
            "sql" => $query,
            "time" => $timetaken,
        ];
        $this->queryLog[$type]["rows"] += $rowsAffected;
    }
    public function setLogQueries(bool $logQueries): bool
    {
        $this->logQueries = $logQueries;
        if ($this->logQueries === true) {
            // Reset the log when enabling
            $this->queryLog = [
                "select" => [
                    "count" => 0,
                    "queries" => [],
                    "rows" => 0,
                ],
                "insert" => [
                    "count" => 0,
                    "queries" => [],
                    "rows" => 0,
                ],
                "update" => [
                    "count" => 0,
                    "queries" => [],
                    "rows" => 0,
                ],
                "delete" => [
                    "count" => 0,
                    "queries" => [],
                    "rows" => 0,
                ],
            ];
        }
        return $this->logQueries;
    }
}
