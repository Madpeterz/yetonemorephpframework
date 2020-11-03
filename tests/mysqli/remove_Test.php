<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\MySQLi\MysqliEnabled as MysqliConnector;

class mysqli_remove_test extends TestCase
{
    /* @var YAPF\MySQLi\MysqliEnabled $sql */
    protected $sql = null;
    protected function setUp(): void
    {
        $this->sql = new MysqliConnector();
    }
    protected function tearDown(): void
    {
        $this->sql->sqlSave(true);
        $this->sql = null;
    }

    public function test_remove()
    {
        $where_config = [
        "fields" => ["id"],
        "values" => [2],
        "matches" => ["="],
        "types" => ["i"]
        ];
        $results = $this->sql->removeV2("endoftestempty", $where_config);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results["status"], true);
        $this->assertSame($results["rowsDeleted"], 1);
        $this->assertSame($results["message"], "ok");
    }

    public function test_remove_invaildtable()
    {
        $where_config = [
            "fields" => ["id"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["i"]
        ];
        $results = $this->sql->removeV2("badtable", $where_config);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results["status"], false);
        $this->assertSame($results["rowsDeleted"], 0);
        $error_msg = "unable to prepair: DELETE FROM badtable";
        $error_msg .= " WHERE id = ? because Table 'test.badtable' doesn't exist";
        $this->assertSame($results["message"], $error_msg);
        $results = $this->sql->removeV2("", $where_config);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results["status"], false);
        $this->assertSame($results["rowsDeleted"], 0);
        $this->assertSame($results["message"], "No table given");
    }

    public function test_remove_invaildfield()
    {
        $where_config = [
        "fields" => ["badtheif"],
        "values" => [1],
        "matches" => ["="],
        "types" => ["i"]
        ];
        $results = $this->sql->removeV2("endoftestempty", $where_config);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results["status"], false);
        $this->assertSame($results["rowsDeleted"], 0);
        $error_msg = "unable to prepair: DELETE FROM endoftestempty";
        $error_msg .= " WHERE badtheif = ? because Unknown column 'badtheif' in 'where clause'";
        $this->assertSame($results["message"], $error_msg);
    }

    public function test_remove_invaildvalue()
    {
        $where_config = [
        "fields" => ["id"],
        "values" => ["1"],
        "matches" => ["="],
        "types" => ["s"]
        ];
        $results = $this->sql->removeV2("endoftestempty", $where_config);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results["status"], true);
        $this->assertSame($results["rowsDeleted"], 1);
        $this->assertSame($results["message"], "ok");

        $where_config = [
        "fields" => ["value"],
        "values" => [null],
        "matches" => ["="],
        "types" => ["i"]
        ];
        $results = $this->sql->removeV2("endoftestempty", $where_config);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results["status"], true);
        $this->assertSame($results["rowsDeleted"], 0);
        $this->assertSame($results["message"], "ok");
    }

    public function test_remove_multiple()
    {
        $where_config = [
        "fields" => ["id"],
        "values" => [2],
        "matches" => ["!="],
        "types" => ["i"]
        ];
        $results = $this->sql->removeV2("endoftestempty", $where_config);
        //[rowsDeleted => int, status => bool, message => string]
        $this->assertSame($results["status"], true);
        $this->assertSame($results["rowsDeleted"], 2);
        $this->assertSame($results["message"], "ok");
    }

    public function test_remove_like()
    {
        $where_config = [
            "fields" => ["name"],
            "values" => ["pondblue"],
            "matches" => ["% LIKE %"],
            "types" => ["s"]
            ];
        $results = $this->sql->removeV2("liketests", $where_config);
        //[rowsDeleted => int, status => bool, message => string]
        error_log(print_r($results, true));
        $this->assertSame($results["status"], true);
        $this->assertSame($results["rowsDeleted"], 2);
        $this->assertSame($results["message"], "ok");
    }
}
