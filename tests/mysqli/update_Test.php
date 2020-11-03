<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\MySQLi\MysqliEnabled as MysqliConnector;

class mysqliUpdateTest extends TestCase
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

    public function test_update()
    {
        $where_config = [
            "fields" => ["id"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["i"],
        ];
        $update_config = [
            "fields" => ["username"],
            "values" => ["NotMadpeter"],
            "types" => ["s"],
        ];
        $results = $this->sql->updateV2("endoftestwithupdates", $update_config, $where_config);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results["status"], true);
        $this->assertSame($results["changes"], 1);
        $this->assertSame($results["message"], "ok");
    }

    public function test_update_invaildtable()
    {
        $where_config = [
            "fields" => ["id"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["i"],
        ];
        $update_config = [
            "fields" => ["username"],
            "values" => ["NotMadpeter"],
            "types" => ["s"],
        ];
        $results = $this->sql->updateV2("badtable", $update_config, $where_config);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results["status"], false);
        $this->assertSame($results["changes"], 0);
        $this->assertSame($results["message"], "unable to prepair: UPDATE badtable SET username=?"
        . " WHERE id = ? because Table 'test.badtable' doesn't exist");

        $results = $this->sql->updateV2("", $update_config, $where_config);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results["status"], false);
        $this->assertSame($results["changes"], 0);
        $this->assertSame($results["message"], "No table given");
    }

    public function test_update_invaildfield()
    {
        $where_config = [
            "fields" => ["missingfield"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["s"]
        ];
        $update_config = [
            "fields" => ["username"],
            "values" => ["NotMadpeter"],
            "types" => ["s"]
        ];
        $results = $this->sql->updateV2("endoftestwithupdates", $update_config, $where_config);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results["status"], false);
        $this->assertSame($results["changes"], 0);
        $this->assertSame($results["message"], "unable to prepair: UPDATE endoftestwithupdates SET"
        . " username=? WHERE missingfield = ? because Unknown column 'missingfield' in 'where clause'");
    }

    public function test_update_invaildvalue()
    {
        $where_config = [
            "fields" => ["id"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["i"]
        ];
        $update_config = [
            "fields" => ["username"],
            "values" => [null],
            "types" => ["s"]
        ];
        $results = $this->sql->updateV2("endoftestwithupdates", $update_config, $where_config);
        // [changes => int, status => bool, message => string]
        error_log(print_r($results, true)); // see why github does not like this.
        $this->assertSame($results["status"], true);
        $this->assertSame($results["changes"], 1);
        $this->assertSame($results["message"], "ok");
    }

    public function test_update_multiple()
    {
        $where_config = [
            "fields" => ["id"],
            "values" => [-1],
            "matches" => ["!="],
            "types" => ["i"]
        ];
        $update_config = [
            "fields" => ["value"],
            "values" => ["magic"],
            "types" => ["s"]
        ];
        $results = $this->sql->updateV2("liketests", $update_config, $where_config);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results["status"], true);
        $this->assertSame($results["changes"], 2);
        $this->assertSame($results["message"], "ok");
    }

    public function test_update_like()
    {
        $where_config = [
            "fields" => ["name"],
            "values" => ["Advent"],
            "matches" => ["% LIKE %"],
            "types" => ["s"]
        ];
        $update_config = [
            "fields" => ["value"],
            "values" => ["woof"],
            "types" => ["s"]
        ];
        $results = $this->sql->updateV2("liketests", $update_config, $where_config);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results["status"], true);
        $this->assertSame($results["changes"], 2);
        $this->assertSame($results["message"], "ok");
    }
}
