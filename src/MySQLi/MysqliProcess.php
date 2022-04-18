<?php

namespace YAPF\Framework\MySQLi;

use mysqli_stmt;

abstract class MysqliProcess extends MysqliWhere
{
    /**
     * processSqlRequest
     * Do stuff then talk to Sql.
     */
    protected function processSqlRequest(
        string $bindText,
        array $bindArgs,
        string &$sql,
        ?array $whereConfig = null,
        ?array $order_config = null,
        ?array $options_config = null,
        ?array $join_tables = null
    ): ?mysqli_stmt {
        $failed = false;
        $failedWhy = "";

        $this->selectBuildJoins($join_tables, $sql, $failed, $failedWhy);
        if ($failed == true) {
            $this->addError("failed with message:" . $failedWhy);
            return null;
        }

        $failed = !$this->processWhere($sql, $whereConfig, $bindText, $bindArgs, $failedWhy, $failed);
        if ($failed == true) {
            $this->addError("Where config failed: " . $failedWhy);
            return null;
        }
        if ($sql == "empty_in_array") {
            $this->addError("Targeting IN|NOT IN with no array");
            return null;
        }
        if (is_array($order_config) == true) {
            $this->buildOrderby($sql, $order_config);
        }
        if (is_array($options_config) == true) {
            $this->buildOption($sql, $options_config);
        }
        return $this->prepareBindExecute($sql, $bindArgs, $bindText);
    }
}
