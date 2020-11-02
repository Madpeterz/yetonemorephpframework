<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\MySQLi\MysqliEnabled as MysqliConnector;

class mysqli_count_test extends TestCase
{
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

    public function test_count_onehundo()
    {
        $results = $this->sql->basicCountV2("counttoonehundo");
        $this->assertSame($results["status"], true);
        $this->assertSame($results["count"], 100);
    }

    public function test_count_no_table()
    {
        $results = $this->sql->basicCountV2("");
        $this->assertSame($results["status"], false);
        $this->assertSame($results["count"], 0);
        $this->assertSame($results["message"], "No table given");
    }

    public function test_count_invaild_table()
    {
        $results = $this->sql->basicCountV2("badtable");
        $this->assertSame($results["status"], false);
        $this->assertSame($results["count"], 0);
        $this->assertSame($results["message"], "Unable to read table");
    }

    public function test_count_onehundo_only_negitive_ids()
    {
        $where_config = [
            "fields" => ["id"],
            "values" => [0],
            "matches" => ["<"],
            "types" => ["i"]
        ];
        $results = $this->sql->basicCountV2("counttoonehundo", $where_config);
        $this->assertSame($results["status"], true);
        $this->assertSame($results["count"], 0);
        $this->assertSame($results["message"], "ok");
    }

    public function test_count_onehundo_only_ids_gtr_60()
    {
        $where_config = [
            "fields" => ["id"],
            "values" => [60],
            "matches" => [">"],
            "types" => ["i"]
        ];
        $results = $this->sql->basicCountV2("counttoonehundo", $where_config);
        $this->assertSame($results["status"], true);
        $this->assertSame($results["count"], 40);
        $this->assertSame($results["message"], "ok");
    }

    public function test_remove_has_emptyed()
    {
        $where_config = [
            "fields" => ["id"],
            "values" => [-1],
            "matches" => ["!="],
            "types" => ["i"]
        ];
        $results = $this->sql->basicCountV2("endoftestempty", $where_config);
        $this->assertSame($results["status"], true);
        $this->assertSame($results["count"], 0);
        $this->assertSame($results["message"], "ok");
    }


    public function test_add_has_added_rows()
    {
        $where_config = [
            "fields" => ["id"],
            "values" => [-1],
            "matches" => ["!="],
            "types" => ["i"]
        ];
        $results = $this->sql->basicCountV2("endoftestempty", $where_config);
        $this->assertSame($results["status"], true);
        $this->assertSame($results["count"], 0);
        $this->assertSame($results["message"], "ok");
    }
}
