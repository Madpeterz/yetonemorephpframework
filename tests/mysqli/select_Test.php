<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\MySQLi\MysqliEnabled as MysqliConnector;

class MysqliSelectTest extends TestCase
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
        $this->assertSame($results["status"], true);
        $this->assertSame($results["message"], "56 commands run");
    }

    public function testSelectBasic()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $result = $this->sql->selectV2($basic_config);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result["message"], "ok");
        $this->assertSame(count($result["dataset"]), 100);
        $this->assertSame($result["status"], true);
    }

    public function testSelectBasicExtended()
    {
        $basic_config = [
            "table" => "counttoonehundo",
            "fields" => ["SUM(cvalue) as total","count(id) as entrys"],
        ];
        $result = $this->sql->selectV2($basic_config);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result["message"], "ok");
        $this->assertSame(count($result["dataset"]), 1);
        $this->assertSame($result["status"], true);
        $this->assertSame($result["dataset"][0]["total"], '10230');
        $this->assertSame($result["dataset"][0]["entrys"], 100);
    }

    public function testSelectOrdering()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $order_config = [
            "ordering_enabled" => true,
            "order_field" => "id",
            "order_dir" => "DESC"
        ];
        $result = $this->sql->selectV2($basic_config, $order_config);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result["message"], "ok");
        $this->assertSame(count($result["dataset"]), 100);
        $this->assertSame($result["status"], true);
        $this->assertSame($result["dataset"][0]["id"], 100);
        $this->assertSame($result["dataset"][0]["cvalue"], 512);
    }

    public function testSelectWhereConfig()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $where_config = [
            "fields" => ["cvalue"],
            "values" => [256],
            "types" => ["i"],
            "matches" => ["<"],
        ];
        $result = $this->sql->selectV2($basic_config, null, $where_config);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result["message"], "ok");
        $this->assertSame(count($result["dataset"]), 80);
        $this->assertSame($result["status"], true);
        $this->assertSame($result["dataset"][0]["id"], 1);
        $this->assertSame($result["dataset"][0]["cvalue"], 1);
    }
}
