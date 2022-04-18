<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;

class mysqli_remove_test extends TestCase
{
    protected ?MysqliConnector $sql;
    protected function setUp(): void
    {
        $this->sql = new MysqliConnector();
    }
    protected function tearDown(): void
    {
        $this->sql->sqlSave(true);
        $this->sql = null;
    }
    public function testRestoreDbFirst()
    {
        $results = $this->sql->rawSQL("tests/testdataset.sql");
        // [status =>  bool, message =>  string]
        $this->assertSame($results->status, true);
        $this->assertSame($results->commandsRun, 56);
    }

    public function testRemove()
    {
        $whereConfig = [
        "fields" => ["id"],
        "values" => [2],
        "matches" => ["="],
        "types" => ["i"]
        ];
        $results = $this->sql->removeV2("endoftestempty", $whereConfig);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results->status, true);
        $this->assertSame($results->itemsRemoved, 1);
        $this->assertSame($results->message, "ok");
    }

    public function testRemoveInValidTable()
    {
        $whereConfig = [
            "fields" => ["id"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["i"]
        ];
        $results = $this->sql->removeV2("badtable", $whereConfig);
        $this->assertSame($results->status, false);
        $this->assertSame($results->itemsRemoved, 0);
        $error_msg = "Unable to prepare: Table 'test.badtable' doesn't exist";
        $this->assertSame($results->message, $error_msg);
        $results = $this->sql->removeV2("", $whereConfig);
        $this->assertSame($results->status, false);
        $this->assertSame($results->itemsRemoved, 0);
        $this->assertSame($results->message, "No table given");
    }

    public function testRemoveInValidField()
    {
        $whereConfig = [
        "fields" => ["badtheif"],
        "values" => [1],
        "matches" => ["="],
        "types" => ["i"]
        ];
        $results = $this->sql->removeV2("endoftestempty", $whereConfig);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results->status, false);
        $this->assertSame($results->itemsRemoved, 0);
        $error_msg = "Unable to prepare: Unknown column 'badtheif' in 'where clause'";
        $this->assertSame($results->message, $error_msg);
    }

    public function testRemoveInValidValue()
    {
        $whereConfig = [
        "fields" => ["id"],
        "values" => ["1"],
        "matches" => ["="],
        "types" => ["s"]
        ];
        $results = $this->sql->removeV2("endoftestempty", $whereConfig);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results->status, true);
        $this->assertSame($results->itemsRemoved, 1);
        $this->assertSame($results->message, "ok");

        $whereConfig = [
        "fields" => ["value"],
        "values" => [null],
        "matches" => ["="],
        "types" => ["i"]
        ];
        $results = $this->sql->removeV2("endoftestempty", $whereConfig);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results->status, true);
        $this->assertSame($results->itemsRemoved, 0);
        $this->assertSame($results->message, "ok");
    }

    public function testRemoveMultiple()
    {
        $whereConfig = [
        "fields" => ["id"],
        "values" => [-1],
        "matches" => ["!="],
        "types" => ["i"]
        ];
        $results = $this->sql->removeV2("endoftestempty", $whereConfig);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results->status, true);
        $this->assertSame($results->itemsRemoved, 2);
        $this->assertSame($results->message, "ok");

    }

    public function testRemoveLike()
    {
        $whereConfig = [
            "fields" => ["name"],
            "values" => ["pondblue"],
            "matches" => ["% LIKE %"],
            "types" => ["s"]
            ];
        $results = $this->sql->removeV2("liketests", $whereConfig);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results->message, "ok");
        $this->assertSame($results->status, true);
        $this->assertSame($results->itemsRemoved, 2);
    }

    public function testRemoveBrokenSqlConnection()
    {
        $this->sql->sqlSave();
        $this->sql->dbUser = "InValid";
        $this->sql->dbPass = null;
        $whereConfig = [
            "fields" => ["name"],
            "values" => ["pondblue"],
            "matches" => ["% LIKE %"],
            "types" => ["s"]
            ];
        $results = $this->sql->removeV2("liketests", $whereConfig);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results->message, "sqlStartConnection returned false!");
        $this->assertSame($results->status, false);
        $this->assertSame($results->itemsRemoved, 0);
    }
}
