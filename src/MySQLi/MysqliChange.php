<?php

namespace YAPF\Framework\MySQLi;

use YAPF\Framework\Responses\MySQLi\AddReply;
use YAPF\Framework\Responses\MySQLi\RemoveReply;
use YAPF\Framework\Responses\MySQLi\UpdateReply;

abstract class MysqliChange extends MysqliProcess
{
    protected array $queryStats = [
        "selects" => 0,
        "updates" => 0,
        "deletes" => 0,
        "adds" => 0,
        "total" => 0,
    ];

    /**
     * > This function returns an array of the number of queries executed by the database class
     * @return mixed[] An array of the query stats.
     */
    public function getSQLstats(): array
    {
        $this->queryStats["total"] = 0 +
        $this->queryStats["selects"] +
        $this->queryStats["adds"] +
        $this->queryStats["updates"] +
        $this->queryStats["deletes"];
        return $this->queryStats;
    }

    /**
     * removeV2
     * takes a V2 where config to remove
     * items from the database
     * $whereConfig: see selectV2.readme
     */
    public function removeV2(string $table, ?array $whereConfig = null): RemoveReply
    {
        if (strlen($table) == 0) {
            $this->addError("No table given");
            return new RemoveReply($this->myLastErrorBasic);
        }
        if ($this->sqlStart() == false) {
            return new RemoveReply($this->myLastErrorBasic);
        }
        $this->queryStats["deletes"]++;
        $sql = "DELETE FROM " . $table . "";
        $stmt = $this->processSqlRequest("", [], $sql, $whereConfig);
        if ($stmt === null) {
            return new RemoveReply($this->myLastErrorBasic);
        }
        $rowsChanged = mysqli_affected_rows($this->sqlConnection);
        $stmt->close();
        if ($rowsChanged > 0) {
            $this->needToSave = true;
        }
        return new RemoveReply("ok", true, $rowsChanged);
    }

    protected function checkUpdateV2(string $table, array $updateConfig): UpdateReply
    {
        if (strlen($table) == 0) {
            $this->addError("No table given");
            return new UpdateReply($this->myLastErrorBasic);
        }
        if (count($updateConfig["types"]) == 0) {
            $this->addError("No types given for update");
            return new UpdateReply($this->myLastErrorBasic);
        }
        if (count($updateConfig["fields"]) != count($updateConfig["values"])) {
            $this->addError("count issue fields <=> values");
            return new UpdateReply($this->myLastErrorBasic);
        }
        if (count($updateConfig["values"]) != count($updateConfig["types"])) {
            $this->addError("count issue values <=> types");
            return new UpdateReply($this->myLastErrorBasic);
        }
        if ($this->sqlStart() == false) {
            return new UpdateReply($this->myLastErrorBasic);
        }
        return new UpdateReply("continue", true);
    }

    /**
     * updateV2
     * takes a V2 update config,
     * and a V2 where config,
     * to apply a change to the database.
     * $updateConfig = ["fields" => string[], "values" => mixed[], "types" => string1[]]
     * $whereConfig: see selectV2.readme
     */
    public function updateV2(string $table, array $updateConfig, ?array $whereConfig = null): UpdateReply
    {
        $reply = $this->checkUpdateV2($table, $updateConfig);
        if ($reply->status == false) {
            return $reply;
        }
        $bindText = "";
        $bindArgs = [];
        $sql = "UPDATE " . $table . " ";
        $loop = 0;
        $addon = "";
        while ($loop < count($updateConfig["values"])) {
            if ($loop == 0) {
                $sql .= "SET ";
            }
            $sql .= $addon;
            $sql .= $updateConfig["fields"][$loop] . "=";
            $updateConfig["values"][$loop] = $this->convertIfBool($updateConfig["values"][$loop]);
            if (($updateConfig["values"][$loop] == null) && ($updateConfig["values"][$loop] !== 0)) {
                $sql .= "NULL";
            } else {
                $sql .= "?";
                $bindText .= $updateConfig["types"][$loop];
                $bindArgs[] = $updateConfig["values"][$loop];
            }
            $addon = ", ";
            $loop++;
        }
        // where fields
        $this->queryStats["updates"]++;
        $stmt = $this->processSqlRequest($bindText, $bindArgs, $sql, $whereConfig);
        if ($stmt === null) {
            return new UpdateReply($this->myLastErrorBasic);
        }
        $changes = mysqli_stmt_affected_rows($stmt);
        $stmt->close();
        $this->needToSave = true;
        return new UpdateReply("ok", true, $changes);
    }

    protected function checkAddV2(array $config): AddReply
    {
        $required_keys = ["table", "fields","values","types"];
        foreach ($required_keys as $key) {
            if (array_key_exists($key, $config) == false) {
                $this->addError("Required key: " . $key . " is missing");
                return new AddReply($this->myLastErrorBasic);
            }
        }
        if (count($config["fields"]) != count($config["values"])) {
            $this->addError("fields and values counts do not match!");
            return new AddReply($this->myLastErrorBasic);
        }
        if (count($config["values"]) != count($config["types"])) {
            $this->addError("values and types counts do not match!");
            return new AddReply($this->myLastErrorBasic);
        }
        if ($this->sqlStart() == false) {
            return new AddReply($this->myLastErrorBasic);
        }
        return new AddReply("continue", true);
    }

    /**
     * addV2
     * takes a V2 add config
     * and inserts it into the database.
     * $config = ["table" => string, "fields" => string[], "values" => mixed[], "types" => string[]]
     */
    public function addV2(array $config = []): AddReply
    {
        $reply = $this->checkAddV2($config);
        if ($reply->status == false) {
            return $reply;
        }
        $this->queryStats["adds"]++;
        $sql = "INSERT INTO " . $config["table"] . " (" . implode(', ', $config["fields"]) . ") VALUES (";
        $loop = 0;
        $bindText = "";
        $bindArgs = [];
        $addon = "";
        while ($loop < count($config["values"])) {
            $sql .= $addon;
            $value = $config["values"][$loop];
            if (($value == null) && ($value !== 0)) {
                $sql .= " NULL";
            } else {
                $sql .= "?";
                $bindText .= $config["types"][$loop];
                $bindArgs[] = $value;
            }
            $addon = " , ";
            $loop++;
        }
        $sql .= ")";
        $stmt = $this->processSqlRequest($bindText, $bindArgs, $sql);
        if ($stmt === null) {
            return new AddReply($this->myLastErrorBasic);
        }
        $newID = mysqli_insert_id($this->sqlConnection);
        $rowsAdded = mysqli_affected_rows($this->sqlConnection);
        if ($rowsAdded > 0) {
            $this->needToSave = true;
        }
        $stmt->close();
        return new AddReply("ok", true, $newID);
    }
}
