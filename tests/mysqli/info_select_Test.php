<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;

class MysqliTestInfoSchema extends TestCase
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
    public function testReadFromInformationSchema()
    {
        $where_config = [
            "fields" => ["REFERENCED_TABLE_NAME"],
            "values" => [null],
            "matches" => ["IS NOT"],
            "types" => ["s"],
        ];

        $basic_config = [
            "table" => "INFORMATION_SCHEMA.KEY_COLUMN_USAGE",
            "fields" => ["TABLE_NAME","COLUMN_NAME","REFERENCED_COLUMN_NAME","REFERENCED_TABLE_NAME"],
        ];

        $results = $this->sql->selectV2($basic_config, null, $where_config);
        $this->assertSame(true,$results["status"], "Unable to read from table with query: ".$this->sql->lastSql);
    }
}