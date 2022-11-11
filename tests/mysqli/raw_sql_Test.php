<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;

class mysqli_raw_test extends TestCase
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

    public function testRawSql()
    {
        $this->sql->fullSqlErrors = true;
        $results = $this->sql->rawSQL("tests/testdataset.sql");
        // [status =>  bool, message =>  string]
        if($results->status == false)
        {
            $this->assertSame("ok", $this->sql->getLastError(), "sql issue");
        }
        $this->assertSame($results->commandsRun, 56);
        $this->assertSame($results->status, true);
        
        $results = $this->sql->rawSQL("tests/fake.sql");
        $this->assertSame($results->message, "Unable to see file to read");
        $this->assertSame($results->status, false);
        
    }
}
