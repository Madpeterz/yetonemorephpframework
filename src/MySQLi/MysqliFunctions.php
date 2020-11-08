<?php

namespace YAPF\MySQLi;

use Exception;

abstract class MysqliFunctions extends MysqliCore
{
    public bool $fullSqlErrors = false;
    protected function selectBuildJoins(array $join_tables, string &$sql, bool &$failed, string &$failed_on): void
    {
        $all_found = true;
        $counts_match = true;
        $required_keys = ["tables","types","onfield_left","onfield_match","onfield_right"];
        foreach ($required_keys as $key) {
            if (array_key_exists($key, $join_tables) == false) {
                $all_found = false;
                break;
            }
        }
        if ($all_found == false) {
            return;
        }
        $last_key = "";
        foreach ($required_keys as $key) {
            if ($last_key != "") {
                if (count($join_tables[$key]) != count($join_tables[$last_key])) {
                    $failed_on = "counts match error " . $key . " <=> " . $last_key;
                    $counts_match = false;
                    break;
                }
            }
            $last_key  = $key;
        }
        if ($counts_match == false) {
            $failed = true;
            return;
        }
        $failed = false;
        $loop = 0;
        while ($loop < count($join_tables["tables"])) {
            $sql .= " " . $join_tables["types"][$loop] . " " . $join_tables["tables"][$loop] . "";
            $sql .= " ON " . $join_tables["onfield_left"][$loop] . " ";
            $sql .= $join_tables["onfield_match"][$loop] . " " . $join_tables["onfield_right"][$loop] . "";
            $loop++;
        }
    }
    protected function selectBuildTableIds(
        ?array $join_tables,
        string &$main_table_id,
        bool &$auto_ids,
        bool &$clean_ids
    ): void {
        if (is_array($join_tables) == true) {
            $main_table_id = "mtb";
            $auto_ids = true;
            $clean_ids = true;
            if (array_key_exists("main_table_id", $join_tables) == true) {
                $main_table_id = $join_tables["main_table_id"];
            }
            if (array_key_exists("autoids", $join_tables) == true) {
                $auto_ids = $join_tables["autoids"];
            }
            if (array_key_exists("cleanids", $join_tables) == true) {
                $clean_ids = $join_tables["cleanids"];
            }
        }
    }

    protected function selectBuildFields(
        string &$sql,
        string $main_table_id,
        bool $auto_ids,
        bool &$clean_ids,
        array $basic_config
    ): void {
        if (array_key_exists("fields", $basic_config) == false) {
            $this->selectFieldsBuilderWildCard($sql, $main_table_id, $clean_ids);
        } else {
            $this->selectFieldsBuilderBasic($sql, $main_table_id, $auto_ids, $clean_ids, $basic_config);
        }
    }
    protected function selectFieldsBuilderBasic(
        string &$sql,
        string $main_table_id,
        bool $auto_ids,
        bool &$clean_ids,
        array $basic_config
    ): void {
        if (($main_table_id != "") && ($auto_ids == true)) {
            $sql .= " " . $main_table_id . "." . implode(", " . $main_table_id . ".", $basic_config["fields"]);
            return;
        }
        $sql .= " " . implode(", ", $basic_config["fields"]);
        $clean_ids = false;
    }
    protected function selectFieldsBuilderWildCard(string &$sql, string $main_table_id, bool &$clean_ids): void
    {
        if ($main_table_id == null) {
            $sql .= " *";
            return;
        }
        $sql .= " " . $main_table_id . ".*";
        $clean_ids = false;
    }
    protected function selectFieldsBuilderWithFunction(
        string &$sql,
        string $main_table_id,
        bool $auto_ids,
        array $basic_config
    ): void {
        $loop = 0;
        $addon = "";
        $sql .= $basic_config["field_function"];
        $sql .= "(";
        $field = $basic_config["fields"][0];
        if (($main_table_id != "") && ($auto_ids == true)) {
            $sql .= $main_table_id;
        }
        $sql .= $field . ")";
    }

    /**
     * prepairBindExecute
     * shared by Add,Remove,Select and Update
     * this runs the code on the database after all needed
     * checks have finished.
     * @return mixed[] [status => bool, message => string, "stm" => false|statement object]
     */
    protected function SQLprepairBindExecute(
        array $error_addon,
        string &$sql,
        array &$bind_args,
        string &$bind_text
    ): array {
        $sql = strtr($sql, ["  " => " "]);
        $sql = trim($sql);
        $this->lastSql = $sql;
        $stmt = $this->sqlConnection->prepare($sql);
        if ($stmt == false) {
            $error_msg = "unable to prepair: " . $this->sqlConnection->error;
            return $this->addError(__FILE__, __FUNCTION__, $error_msg, $error_addon);
        }
        if (count($bind_args) > 0) {
            try {
                mysqli_stmt_bind_param($stmt, $bind_text, ...$bind_args);
            } catch (Exception $e) {
                $stmt->close();
                $error_msg = "Unable to bind to statement";
                if ($this->fullSqlErrors == true) {
                    $error_msg .= ": ";
                    $error_msg .= $e->getMessage();
                }
                return $this->addError(__FILE__, __FUNCTION__, $error_msg, $error_addon);
            }
        }

        $execute_result = $stmt->execute();
        if ($execute_result == false) {
            $error_msg = "unable to execute because: " . $stmt->error;
            $stmt->close();
            return $this->addError(__FILE__, __FUNCTION__, $error_msg, $error_addon);
        }
        return ["status" => true, "message" => "ok", "stmt" => $stmt];
    }
    /**
     * hasDbConfig
     * Checks if the set database user is in the disallowed list
     */
    public function hasDbConfig(): bool
    {
        $disallowed_users = ["[DB_USER]", "", " "];
        return !in_array($this->dbUser, $disallowed_users);
    }
    /**
     * flagError
     * sets the hadErrors flag to true
     */
    public function flagError(): void
    {
        $this->hadErrors = true;
    }
    /**
     * sqlStartConnection
     * Attempts to create a mysql connection
     * and returns the result of this test
     * - if stop is set to false the connection is kept
     * open, good if you need to hop to other databases.
     */
    public function sqlStartConnection(
        string $user,
        string $pass,
        string $db,
        bool $stop = false,
        ?string $host = null,
        int $timeout = 3
    ): bool {
        $this->sqlStop();
        $this->sqlConnection = mysqli_init();
        mysqli_options($this->sqlConnection, MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
        if ($host == null) {
            $host = $this->dbHost;
        }
        try {
            $status = mysqli_real_connect($this->sqlConnection, $host, $user, $pass, $db);
            if ($status == true) {
                if ($stop == true) {
                    $this->sqlStop();
                }
            }
            return $status;
        } catch (Exception $e) {
            if ($this->fullSqlErrors == true) {
                $this->addError(__FILE__, __FUNCTION__, "SQL connection error: " . $e->getMessage());
            } else {
                $this->addError(__FILE__, __FUNCTION__, "Connect attempt died in a fire");
            }
            return false;
        }
    }
    /**
     * sqlSave
     * if there has been no errors and its marked as need to save
     * close the transaction applying the changes to the database
     * for reals.
     * the stop flag is set to true, so this would close
     * the SQL connection, if you want todo more changes
     * then set stop to false.
     * returns true if saved, false if rollback
     */
    public function sqlSave(bool $stop = true): bool
    {
        $commit_status = false;
        if (($this->hadErrors == false) && ($this->needToSave == true)) {
            $commit_status = $this->sqlConnection->commit();
            if ($commit_status == false) {
                $error_msg = "SQL error [Commit]: " . $this->sqlConnection->error;
                $this->addError(__FILE__, __FUNCTION__, $error_msg);
            }
        } elseif (($this->hadErrors == true) && ($this->needToSave == true)) {
            $this->sqlRollBack();
        }
        if ($stop == true) {
            $this->sqlStop();
        }
        return $commit_status;
    }
    /**
     * sqlRollBack
     * rolls back the changes that have been made to the database
     * from the last Save or new connection.
     * also closes the SQL connection as we should stop
     * what we are doing if there needs to be a rollback.
     */
    public function sqlRollBack(): void
    {
        if ($this->sqlConnection != null) {
            $this->sqlConnection->rollback();
        }
        $this->sqlStop();
    }
    /**
     * sqlRollBack
     * rolls back the changes that have been made to the database
     * from the last Save or new connection.
     * also closes the SQL connection as we should stop
     * what we are doing if there needs to be a rollback.
     */
    public function sqlStart(): bool
    {
        if ($this->sqlConnection != null) {
            return true; // connection is already open!
        }
        if ($this->hasDbConfig() == false) {
            $error_msg = "DB config is not vaild to start!";
            $this->addError(__FILE__, __FUNCTION__, $error_msg);
            return false;
        }
        if ($this->dbPass === null) {
            $error_msg = "DB config password is null!";
            $this->addError(__FILE__, __FUNCTION__, $error_msg);
            $this->dbPass = "";
        }
        $status = $this->sqlStartConnection($this->dbUser, $this->dbPass, $this->dbName, false, $this->dbHost, 5);
        if ($status == true) {
            $this->sqlConnection->autocommit(false); // disable auto commit.
        }
        return $status;
    }
    /**
     * sqlStop
     * stops the open sql connection
     * without saving anything and resets
     * the flags.
     * returns true if a currently open connection
     * was closed.
     */
    protected function sqlStop(): bool
    {
        $this->hadErrors = false;
        $this->needToSave = false;
        if ($this->sqlConnection != null) {
            $this->sqlConnection->close();
            $this->sqlConnection = null;
            return true;
        }
        return false;
    }
}
