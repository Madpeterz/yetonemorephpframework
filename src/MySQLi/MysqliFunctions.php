<?php

namespace YAPF\Framework\MySQLi;

use App\Db as Db;
use mysqli;
use mysqli_stmt;
use Throwable;
use YAPF\Framework\Responses\MySQLi\RawReply;

abstract class MysqliFunctions extends Db
{
    public bool $fullSqlErrors = false;
    protected ?mysqli $sqlConnection = null;
    protected $hadErrors = false;
    protected $needToSave = false;

    public function getNeedsCommit(): bool
    {
        return $this->needToSave;
    }

    public $lastSql = "";
    protected string $charSet = "utf8mb4";

    /*
        changeCharset
        must be called before doing anything with the DB.
        stopping sql also works.
    */
    public function changeCharset(string $charset): void
    {
        if ($this->sqlConnection == null) {
            $this->charSet = $charset;
        }
    }


    protected bool $ExpectedErrorFlag = false;
    public function setExpectedErrorFlag(bool $flagStatus = false): void
    {
        $this->ExpectedErrorFlag = $flagStatus;
    }

    public function getDatabaseName(): string
    {
        return $this->dbName;
    }

    public function __destruct()
    {
        $this->shutdown();
    }

    public function shutdown(): bool
    {
        $this->myLastErrorBasic = "Not connected";
        $result = true;
        if ($this->sqlConnection != null) {
            $result = $this->sqlSave();
        }
        return $result;
    }
    /**
     * getLastSql
     * returns the last SQL statement processed
     * good if you want to check what its doing
     */
    public function getLastSql(): string
    {
        return $this->lastSql;
    }

        /**
     * buildOrderby
     * returns the last SQL statement processed
     * good if you want to check what its doing
     */
    protected function buildOrderby(
        string &$sql,
        array $order
    ): void {
        if (array_key_exists("enabled", $order) == true) {
            if (array_key_exists("byField", $order) == false) {
                $order["byField"] = "id";
                $order["dir"] = "DESC";
                $order["enabled"] = true;
            }
        }
        if ($order["enabled"] == true) {
            if (array_key_exists("as_string", $order) == true) {
                $sql .= " ORDER BY " . $order["as_string"] . " ";
            } else {
                $sql .= " ORDER BY " . $order["byField"] . " " . $order["dir"] . " ";
            }
        }
    }
    /**
     * buildSelectOption
     * processes the options settings for limit and offset
     */
    protected function buildOption(string &$sql, array $options): void
    {
        if (array_key_exists("groupBy", $options) == true) {
            $sql .= " GROUP BY " . $options["groupBy"];
        }
        if (array_key_exists("limit", $options) == false) {
            return;
        }
        if (array_key_exists("pageNumber", $options) == false) {
            if ($options["limit"] > 0) {
                $sql .= " LIMIT " . $options["limit"];
            }
            return;
        }
        if ($options["pageNumber"] > 0) {
            $offset = $options["pageNumber"] * $options["limit"];
            $sql .= " LIMIT " . $options["limit"] . " OFFSET " . $offset;
            return;
        } elseif ($options["limit"] > 0) {
            $sql .= " LIMIT " . $options["limit"];
        }
    }
    /**
     * convertIfBool
     * takes a input and if its a bool converts it to a int
     * otherwise returns input
     */
    public function convertIfBool($input): mixed
    {
        if ($input === false) {
            return 0;
        } elseif ($input === true) {
            return 1;
        }
        return $input;
    }

    /**
     * RawSQL
     * runs a stored sql file from disk
     */
    public function rawSQL(string $path_to_file): RawReply
    {
        if (file_exists($path_to_file) == false) {
            $this->addError("Unable to see file to read");
            return new RawReply($this->myLastErrorBasic);
        }
        if ($this->sqlStart() == false) {
            return new RawReply($this->myLastErrorBasic);
        }

        $commands = [];
        $lines = file($path_to_file);
        if (count($lines) == 0) {
            $this->addError("File is empty");
            return new RawReply($this->myLastErrorBasic);
        }

        $current_command = "";
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (
                (strlen($trimmed) > 0) // not empty
                && (stripos($trimmed, "--")  === false) // not a SQL comment
                && (stripos($trimmed, "/*") === false) // not a magic mysql command
                && (stripos($trimmed, "*") !== 0) // part of multiline comment
                && (stripos($trimmed, "*\\") !== 0) // ending of multiline comment
            ) {
                $current_command .= " " . $trimmed;
                if (substr($trimmed, -1) == ';') {
                    $commands[] = $current_command;
                    $current_command = "";
                }
            }
        }

        $current_command = trim($current_command);
        if ($current_command != "") {
            $this->addError("Warning: raw sql has no ending ;");
            $commands[] = $current_command . ";";
        }
        if (count($commands) == 0) {
            $this->addError("No commands processed from file");
            return new RawReply($this->myLastErrorBasic);
        }

        $had_error = false;
        $commands_run = 0;
        foreach ($commands as $command) {
            $this->lastSql = $command;
            try {
                if ($this->sqlConnection->real_query($command) == false) {
                    $had_error = true;
                    break;
                }
                $this->sqlConnection->store_result();
                $commands_run++;
            } catch (Throwable $e) {
                $had_error = true;
                break;
            }
        }

        if ($had_error == true) {
            $this->addError($this->sqlConnection->error);
            return new RawReply($this->myLastErrorBasic);
        }
        $this->needToSave = true;
        return new RawReply("ok", true, $commands_run);
    }
    protected function selectBuildJoins(?array $join_tables, string &$sql, bool &$failed, string &$failedWhy): void
    {
        if ($join_tables == null) {
            return;
        }
        $all_found = true;
        $counts_match = true;
        $required_keys = ["tables","types","onFieldLeft","onFieldMatch","onFieldRight"];
        $missing_join_key = "";
        foreach ($required_keys as $key) {
            if (array_key_exists($key, $join_tables) == false) {
                $missing_join_key = $key;
                $all_found = false;
                break;
            }
        }
        if ($all_found == false) {
            $failedWhy = "Join tables config missing key: " . $missing_join_key;
            return;
        }
        $last_key = "";
        foreach ($required_keys as $key) {
            if ($last_key != "") {
                if (count($join_tables[$key]) != count($join_tables[$last_key])) {
                    $failedWhy = "counts match error " . $key . " <=> " . $last_key;
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
            if ($join_tables["onFieldLeft"][$loop] != "") {
                $sql .= " ON " . $join_tables["onFieldLeft"][$loop] . " ";
                $sql .= $join_tables["onFieldMatch"][$loop] . " " . $join_tables["onFieldRight"][$loop] . "";
            }
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
            $clean_ids = true;
            if (array_key_exists("main_table_id", $join_tables) == true) {
                $main_table_id = $join_tables["main_table_id"];
                $auto_ids = false;
            }
            if (array_key_exists("cleanIds", $join_tables) == true) {
                $clean_ids = $join_tables["cleanIds"];
                $auto_ids = false;
            }
        }
    }

    protected function selectBuildFields(
        string &$sql,
        array $basic_config
    ): void {
        if (array_key_exists("fields", $basic_config) == false) {
            $sql .= " *";
        } else {
            $sql .= " " . implode(", ", $basic_config["fields"]);
        }
    }
    /**
     * prepareBindExecute
     * shared by Add,Remove,Select and Update
     * this runs the code on the database after all needed
     * checks have finished.
     */
    protected function prepareBindExecute(
        string &$sql,
        array &$bindArgs,
        string &$bindText
    ): ?mysqli_stmt {
        $sql = strtr($sql, ["  " => " "]);
        $sql = trim($sql);
        $this->lastSql = $sql;
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
        if (count($bindArgs) > 0) {
            try {
                $result = mysqli_stmt_bind_param($stmt, $bindText, ...$bindArgs);
                if ($result === false) {
                    throw new Throwable(
                        "mysqli_stmt_bind_param has failed :" . json_encode($stmt->error_list),
                        911,
                        null
                    );
                }
            } catch (Throwable $e) {
                $stmt->free_result();
                $stmt->close();
                $error_msg = "Unable to bind to statement";
                if ($this->fullSqlErrors == true) {
                    $error_msg .= ": ";
                    $error_msg .= $e->getMessage();
                }
                $this->addError($error_msg);
                return null;
            }
        }
        try {
            $execute_result = $stmt->execute();
            if ($execute_result == false) {
                $error_msg = "Unable to execute because: " . $stmt->error;
                $stmt->free_result();
                $stmt->close();
                $this->addError($error_msg);
                return null;
            }
            return $stmt;
        } catch (Throwable $e) {
            $error_msg = "Unable to execute because: " . $e->getMessage();
            $this->addError($error_msg);
            return null;
        }
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
                if ($stop == false) {
                    $this->sqlConnection->set_charset($this->charSet);
                }
            }
            return $status;
        } catch (Throwable $e) {
            if ($this->fullSqlErrors == true) {
                $this->addError("SQL connection error: " . $e->getMessage());
            } else {
                $this->addError("Connect attempt died in a fire");
            }
            return false;
        }
    }

    protected function endSQL(bool $shouldStop, bool $returnStatus): bool
    {
        if ($shouldStop == true) {
            $this->sqlStop();
        }
        return $returnStatus;
    }

    /**
     * sqlSave
     * if there has been no errors and its marked as need to save
     * close the transaction applying the changes to the database
     * for reals.
     * the stop flag is set to true, so this would close
     * the SQL connection, if you want todo more changes
     * then set stop to false.
     * returns true if saved (or no changes), false if rollback
     */
    public function sqlSave(bool $stop = true): bool
    {
        if ($this->needToSave == false) {
            $this->myLastErrorBasic = "No changes made";
            return $this->endSQL($stop, true);
        }
        if ($this->hadErrors == true) {
            $this->addError("Unable to save there are reported errors - attempting rollback");
            $this->sqlRollBack();
            return $this->endSQL($stop, false);
        }
        $commit_status = $this->sqlConnection->commit();
        if ($commit_status == false) {
            $this->myLastErrorBasic = "Commit error";
            $error_msg = "SQL error [Commit]: " . $this->sqlConnection->error;
            $this->addError($error_msg);
        }
        return $this->endSQL($stop, $commit_status);
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
            $error_msg = "DB config is not valid to start!";
            $this->addError($error_msg);
            return false;
        }
        if ($this->dbPass === null) {
            $error_msg = "DB config password is null!";
            $this->addError($error_msg);
            $this->dbPass = "";
        }
        $status = $this->sqlStartConnection($this->dbUser, $this->dbPass, $this->dbName, false, $this->dbHost, 5);
        if ($status == false) {
            $this->addError("sqlStartConnection returned false!");
            return false;
        }
        if ($this->sqlConnection == null) {
            $this->addError("sql connection is not open as expected!");
            return false;
        }
        $this->sqlConnection->autocommit(false); // disable auto commit.
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
