<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\MySQLi\MysqliEnabled as MysqliConnector;

class mysqli_raw_test extends TestCase
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

    public function test_raw_sql()
    {
        $results = $this->sql->rawSQL("tests/testdataset.sql");
        // [status =>  bool, message =>  string]
        $this->assertSame($results["status"], true);
        $this->assertSame($results["message"], "51 commands run");
        $results = $this->sql->rawSQL("tests/fake.sql");
        $this->assertSame($results["status"], false);
        $this->assertSame($results["message"], "Unable to see file to read");
    }
}
