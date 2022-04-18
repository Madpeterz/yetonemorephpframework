<?php

namespace YAPF\Framework\MySQLi;

use Throwable;
use mysqli_stmt;
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

    /**
     * selectV2
     * for a full breakdown of all the magic
     * please see the selectV2.readme
     */
    public function selectV2(
        array $basic_config,
        ?array $order_config = null,
        ?array $where_config = null,
        ?array $options_config = null,
        ?array $join_tables = null,
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
        $main_table_id = "";
        $auto_ids = false;

        $this->selectBuildTableIds($join_tables, $main_table_id, $auto_ids, $clean_ids);
        $sql = "SELECT ";
        $this->selectBuildFields($sql, $basic_config);
        $sql .= " FROM " . $basic_config["table"] . " " . $main_table_id . " ";
        $this->queryStats["selects"]++;
        $stmt = $this->processSqlRequest(
            "",
            [],
            $sql,
            $where_config,
            $order_config,
            $options_config,
            $join_tables
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
     * searchTables
     * searchs multiple tables to find a match
     * returns an array in the dataset of the following
     * [targetfield => value, source => table found in]
     */
    public function searchTables(
        array $target_tables,
        string $match_field,
        $match_value,
        string $match_type = "s",
        string $match_code = "=",
        int $limit_amount = 1,
        string $target_field = "id"
    ): SelectReply {
        if (count($target_tables) <= 1) {
            $this->addError("Requires 2 or more tables to use search");
            return new SelectReply($this->myLastErrorBasic);
        }
        if (strlen($match_field) == 0) {
            $this->addError("Requires a match field to be sent");
            return new SelectReply($this->myLastErrorBasic);
        }
        if (in_array($match_type, ["s", "d", "i", "b"]) == false) {
            $this->addError("Match type is not vaild");
            return new SelectReply($this->myLastErrorBasic);
        }
        $match_symbol = "?";
        if ($match_value === null) {
            $match_symbol = "NULL";
            if (in_array($match_code, ["IS","IS NOT"]) == false) {
                $this->addError("Match value can not be null");
                return new SelectReply($this->myLastErrorBasic);
            }
        }
        if ($this->sqlStart() == false) {
            return new SelectReply($this->myLastErrorBasic);
        }
        $bind_args = [];
        $bind_text = "";
        $sql = "";
        $addon = "";
        $table_id = 1;
        foreach ($target_tables as $table) {
            $sql .= $addon;
            $sql .= "(SELECT tb" . $table_id . "." . $target_field . ", '";
            $sql .= $table . "' AS source FROM " . $table . " tb" . $table_id . "";
            $sql .= " WHERE tb" . $table_id . "." . $match_field . " " . $match_code . " " . $match_symbol . " ";
            if ($limit_amount > 0) {
                $sql .= "LIMIT " . $limit_amount;
            }
            $sql .= ")";
            $addon = " UNION ALL ";
            if ($match_symbol == "?") {
                $bind_args[] = $match_value;
                $bind_text .= $match_type;
            }
            $table_id++;
        }
        $sql .= " ORDER BY id DESC";
        $stmt = $this->SQLprepairBindExecute($sql, $bind_args, $bind_text);
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
