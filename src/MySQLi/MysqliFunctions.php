<?php

namespace YAPF\MySQLi;

use Exception;

abstract class MysqliFunctions extends MysqliCore
{
    public bool $fullConnectionError = false;
    /**
     * prepairBindExecute
     * shared by Add,Remove,Select and Update
     * this runs the code on the database after all needed
     * checks have finished.
     * @return mixed[] [status => bool, message => string, "stm" => false|statement object]
     */
    protected function SQLprepairBindExecute(string &$sql, array &$bind_args, string &$bind_text): array
    {
        $this->lastSql = $sql;
        $stmt = $this->sqlConnection->prepare($sql);
        if ($stmt == false) {
            $error_msg = "unable to prepair: " . $this->sqlConnection->error;
            return ["status" => false, "message" => $error_msg, "stmt" => false];
        }
        $bind_ok = true;
        if (count($bind_args) > 0) {
            $bind_ok = mysqli_stmt_bind_param($stmt, $bind_text, ...$bind_args);
        }
        if ($bind_ok == false) {
            $error_msg = "unable to bind because: " . $stmt->error;
            $stmt->close();
            return ["status" => false, "message" => $error_msg, "stmt" => false];
        }
        $execute_result = $stmt->execute();
        if ($execute_result == false) {
            $error_msg = "unable to execute because: " . $stmt->error;
            $stmt->close();
            return ["status" => false, "message" => $error_msg, "stmt" => false];
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
            } else {
                $this->addError(__FILE__, __FUNCTION__, "mysqli_real_connect has failed");
            }
            return $status;
        } catch (Exception $e) {
            if ($this->fullConnectionError == true) {
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
