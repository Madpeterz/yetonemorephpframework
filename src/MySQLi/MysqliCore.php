<?php

namespace YAPF\MySQLi;

use App\Db as Db;

abstract class MysqliCore extends Db
{
    protected $sqlConnection = null;
    protected $hadErrors = false;
    protected $needToSave = false;
    public $lastSql = "";
    protected $track_table_select_access = false;
    protected $track_select_from_tables = [];

    public function __destruct()
    {
        $this->shutdown();
    }

    public function shutdown(): bool
    {
        $change_status = false;
        if ($this->sqlConnection != null) {
            if (($this->hadErrors == false) && ($this->needToSave == true)) {
                $change_status = $this->sqlConnection->commit();
            } else {
                $this->sqlConnection->rollback();
            }
            $last_error = "Errors reported by SQL";
            if ($this->hadErrors == false) {
                $last_error = "changes commited to DB";
                if ($this->needToSave == false) {
                    $last_error = "No changes made";
                }
                if ($this->needToSave != $change_status) {
                    if ($this->needToSave == true) {
                        $last_error = "Failed to write commit to db";
                    }
                }
            }

            $this->myLastErrorBasic = $last_error;
            return $this->sqlStop();
        }
        $this->myLastErrorBasic = "Not connected";
        return false;
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
}
