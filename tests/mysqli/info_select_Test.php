<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\MySQLi\MysqliEnabled as MysqliConnector;

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
        $basic_config = [
            "table" => "information_schema.INNODB_SYS_FOREIGN",
            "fields" => ["ID","REF_NAME"],
        ];
        
        $results = $this->sql->selectV2($basic_config, null);
        $this->assertSame(true,$results["status"], "Unable to read from table with query: ".$this->sql->lastSql);

        $basic_config = [
            "table" => "information_schema.INNODB_SYS_FOREIGN_COLS",
            "fields" => ["ID","FOR_COL_NAME","REF_COL_NAME"],
        ];
        $results = $this->sql->selectV2($basic_config);
        $this->assertSame(true,$results["status"], "Unable to read from table with query: ".$this->sql->lastSql);
    }
}