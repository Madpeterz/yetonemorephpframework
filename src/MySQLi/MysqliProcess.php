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
        string $bind_text,
        array $bind_args,
        string &$sql,
        ?array $where_config = null,
        ?array $order_config = null,
        ?array $options_config = null,
        ?array $join_tables = null
    ): ?mysqli_stmt {
        $failed = false;
        $failed_on = "";

        $this->selectBuildJoins($join_tables, $sql, $failed, $failed_on);
        if ($failed == true) {
            $this->addError("failed with message:" . $failed_on);
            return null;
        }

        $failed = !$this->processWhere($sql, $where_config, $bind_text, $bind_args, $failed_on, $failed);
        if ($failed == true) {
            $this->addError("Where config failed: " . $failed_on);
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
        return $this->SQLprepairBindExecute($sql, $bind_args, $bind_text);
    }
}
