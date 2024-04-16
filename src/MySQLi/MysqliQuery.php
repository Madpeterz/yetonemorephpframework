<?php

namespace YAPF\Framework\MySQLi;

use Throwable;
use YAPF\Framework\Responses\MySQLi\SelectReply;

abstract class MysqliQuery extends MysqliChange
{
    protected int $sql_selects = 0;
    /**
     * @deprecated
     * getSQLselectsCount
     * This is being replaced with getSQLstats
     */
    public function getSQLselectsCount(): int
    {
        return $this->sql_selects;
    }

    public function directSelectSQL(string $sqlRaw): SelectReply
    {
        if ($this->sqlStart() == false) {
            return new SelectReply($this->myLastErrorBasic);
        }
        $bindArgs = [];
        $bindText = "";
        $stmt = $this->prepareBindExecute($sqlRaw, $bindArgs, $bindText);
        if ($stmt === null) {
            return new SelectReply($this->myLastErrorBasic);
        }
        $result = false;
        try {
            $result = $stmt->get_result();
        } catch (Throwable $e) {
            $stmt->close();
            $this->addError("statement failed due to error: " . $e);
            return new SelectReply($this->myLastErrorBasic);
        }
        $dataset = $this->buildDataset(false, $result);
        $stmt->free_result();
        $stmt->close();
        $this->sqlConnection->next_result();
        $this->sql_selects++;
        return new SelectReply("ok", true, $dataset);
    }

    /**
     * selectV2
     * for a full breakdown of all the magic
     * please see the selectV2.readme
     */
    public function selectV2(
        array $basic_config,
        ?array $order_config = null,
        ?array $whereConfig = null,
        ?array $options_config = null,
        ?array $joinTables = null,
        bool $clean_ids = false
    ): SelectReply {
        if (array_key_exists("table", $basic_config) == false) {
            $this->addError("table index missing from basic config!");
            return new SelectReply($this->myLastErrorBasic);
        }
        if (strlen($basic_config["table"]) == 0) {
            $this->addError("No table set in basic config!");
            return new SelectReply($this->myLastErrorBasic);
        }
        if ($this->sqlStart() == false) {
            return new SelectReply($this->myLastErrorBasic);
        }
        $mainTableId = "";
        $auto_ids = false;

        $this->selectBuildTableIds($joinTables, $mainTableId, $auto_ids, $clean_ids);
        $sql = "SELECT ";
        $this->selectBuildFields($sql, $basic_config);
        $sql .= " FROM " . $basic_config["table"] . " " . $mainTableId . " ";
        $this->queryStats["selects"]++;
        $stmt = $this->processSqlRequest(
            "",
            [],
            $sql,
            $whereConfig,
            $order_config,
            $options_config,
            $joinTables
        );
        if ($stmt === null) {
            return new SelectReply($this->myLastErrorBasic);
        }
        $result = false;
        try {
            $result = $stmt->get_result();
        } catch (Throwable $e) {
            $stmt->close();
            $this->addError("statement failed due to error: " . $e);
            return new SelectReply($this->myLastErrorBasic);
        }
        $dataset = $this->buildDataset($clean_ids, $result);
        $stmt->free_result();
        $stmt->close();
        $this->sqlConnection->next_result();
        $this->sql_selects++;
        return new SelectReply("ok", true, $dataset);
    }
    /**
     *  buildDataset
     *  $result expects mysqli_result or false
     *  @return mixed[] returns the dataset in keyValue pairs or a empty array
     */
    protected function buildDataset(bool $clean_ids, $result): array
    {
        $dataset = [];
        if ($result == false) {
            return $dataset;
        }
        if ($clean_ids == true) {
            while ($entry = $result->fetch_assoc()) {
                $dataset[] = $entry;
            }
            return $dataset;
        }
        $loop = 0;
        while ($entry = $result->fetch_assoc()) {
            $cleaned_entry = [];
            foreach ($entry as $field => $value) {
                $cleaned_entry[$field] = $value;
            }
            $dataset[] = $cleaned_entry;
            $loop++;
        }
        return $dataset;
    }

    /**
     * > This function checks the parameters sent to the SearchTables function
     * @param array targetTables An array of table names to search.
     * @param string matchField The field to match on.
     * @param matchValue The value to match against.
     * @param string matchType s = string, d = date, i = integer, b = boolean
     * @param string matchCode The code to use for the match.  This can be any valid SQL code.  For
     * example, "=", ">", "<", ">=", "<=", "LIKE", "NOT LIKE", "IS", "IS NOT", etc.
     * @return SelectReply A SelectReply object.
     */
    protected function checkSearchTables(
        array $targetTables,
        string $matchField,
        $matchValue,
        string $matchType = "s",
        string $matchCode = "=",
    ): SelectReply {
        if (count($targetTables) <= 1) {
            $this->addError("Requires 2 or more tables to use search");
            return new SelectReply($this->myLastErrorBasic);
        }
        if (strlen($matchField) == 0) {
            $this->addError("Requires a match field to be sent");
            return new SelectReply($this->myLastErrorBasic);
        }
        if (in_array($matchType, ["s", "d", "i", "b"]) == false) {
            $this->addError("Match type is not valid");
            return new SelectReply($this->myLastErrorBasic);
        }
        if ($matchValue === null) {
            if (in_array($matchCode, ["IS", "IS NOT"]) == false) {
                $this->addError("Match value can not be null");
                return new SelectReply($this->myLastErrorBasic);
            }
        }
        if ($this->sqlStart() == false) {
            return new SelectReply($this->myLastErrorBasic);
        }
        return new SelectReply("continue", true);
    }

    /**
     * searchTables
     * Search multiple tables for a value in a field
     * @param array targetTables An array of table names to search.
     * @param string matchField The field to match against.
     * @param mixed matchValue The value to match against.
     * @param string matchType s = string, i = integer, d = double, b = blob
     * @param string matchCode The operator to use in the WHERE clause.
     * @param int limit The maximum number of results to return.
     * @param string targetField The field you want to return.
     */
    public function searchTables(
        array $targetTables,
        string $matchField,
        $matchValue,
        string $matchType = 's',
        string $matchCode = "=",
        int $limit = 1,
        string $targetField = "id"
    ): SelectReply {
        $check = $this->checkSearchTables(
            $targetTables,
            $matchField,
            $matchValue,
            $matchType,
            $matchCode
        );
        if ($check->status == false) {
            return $check;
        }
        $match_symbol = "?";
        if ($matchValue === null) {
            $match_symbol = "NULL";
        }

        $bindArgs = [];
        $bindText = "";
        $sql = "";
        $addon = "";
        $table_id = 1;
        foreach ($targetTables as $table) {
            $sql .= $addon;
            $sql .= "(SELECT tb" . $table_id . "." . $targetField . ", '";
            $sql .= $table . "' AS source FROM " . $table . " tb" . $table_id . "";
            $sql .= " WHERE tb" . $table_id . "." . $matchField . " " . $matchCode . " " . $match_symbol . " ";
            if ($limit > 0) {
                $sql .= "LIMIT " . $limit;
            }
            $sql .= ")";
            $addon = " UNION ALL ";
            if ($match_symbol == "?") {
                $bindArgs[] = $matchValue;
                $bindText .= $matchType;
            }
            $table_id++;
        }
        $sql .= " ORDER BY id DESC";
        $stmt = $this->prepareBindExecute($sql, $bindArgs, $bindText);
        if ($stmt === null) {
            return new SelectReply($this->myLastErrorBasic);
        }
        $result = $stmt->get_result();
        $dataset = [];
        $loop = 0;
        while ($entry = $result->fetch_assoc()) {
            $dataset[$loop] = $entry;
            $loop++;
        }
        return new SelectReply("ok", true, $dataset);
    }
}
