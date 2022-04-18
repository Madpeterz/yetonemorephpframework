<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;

class mysqli_add_test extends TestCase
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

    public function testAdd()
    {
        $loop = 0;
        while ($loop < 4) {
            $config = [
                "table" => "endoftestwithfourentrys",
                "fields" => ["value"],
                "values" => [sha1("testAdd" . $loop)],
                "types" => ["s"]
            ];
            $results = $this->sql->addV2($config);
            // [newID => ?int, rowsAdded => int, status => bool, message => string]
            $this->assertSame($results->message, "ok");
            $this->assertSame($results->status, true);
            $this->assertGreaterThan(0, $results->newid);
            $loop++;
        }
    }

    public function test_add_invaildtable()
    {
        $config = [
            "table" => "badtable",
            "fields" => ["value"],
            "values" => ["testAdd1"],
            "types" => ["s"]
        ];
        $results = $this->sql->addV2($config);
        // [newID => ?int, rowsAdded => int, status => bool, message => string]
        $this->assertSame($results->status, false);
        $this->assertSame(
            $results->message,
            "Unable to prepair: Table 'test.badtable' doesn't exist"
        );
        $this->assertSame($results->newid, null);
    }

    public function test_add_invaildfield()
    {
        $config = [
            "table" => "endoftestwithfourentrys",
            "fields" => ["badfield"],
            "values" => ["testAdd1"],
            "types" => ["s"]
        ];
        $results = $this->sql->addV2($config);
        // [newID => ?int, rowsAdded => int, status => bool, message => string]
        $this->assertSame($results->status, false);
        $error_msg = "Unable to prepair: Unknown column 'badfield' in 'field list'";
        $this->assertSame($results->message, $error_msg);
        $this->assertSame($results->newid, null);
    }

    public function test_add_invaildvalue()
    {
        $config = [
            "table" => "endoftestwithfourentrys",
            "fields" => ["value"],
            "values" => [null],
            "types" => ["s"]
        ];
        $results = $this->sql->addV2($config);
        $this->assertSame($results->status, false);
        $error_msg = "Unable to execute because: Column 'value' cannot be null";
        $this->assertSame($results->message, $error_msg);
        $this->assertSame($results->newid, null);

        $config = [
            "table" => "alltypestable",
            "fields" => ["stringfield","intfield", "floatfield"],
            "values" => [1.43, "44", 44.54],
            "types" => ["d", "s", "i"]
        ];
        $results = $this->sql->addV2($config);
        $this->assertSame($results->status, true);
        $this->assertSame($results->message, "ok");
        $this->assertGreaterThan(0, $results->newid);
    }
}
