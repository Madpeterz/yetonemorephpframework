<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;

class MysqliCountTest extends TestCase
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

    public function testCountOnehundo()
    {
        $results = $this->sql->basicCountV2("counttoonehundo");
        $this->assertSame($results->message, "ok");
        $this->assertSame($results->status, true);
        $this->assertSame($results->items, 100);
    }

    public function testCountEmpty()
    {
        $results = $this->sql->basicCountV2("rollbacktest");
        $this->assertSame($results->message, "ok");
        $this->assertSame($results->status, true);
        $this->assertSame($results->items, 0);
    }

    public function testCountNoTable()
    {
        $results = $this->sql->basicCountV2("");
        $this->assertSame($results->status, false);
        $this->assertSame($results->items, 0);
        $this->assertSame($results->message, "No table given");
    }

    public function testCountInValidTable()
    {
        $results = $this->sql->basicCountV2("badtable");
        $this->assertSame($results->status, false);
        $this->assertSame($results->items, 0);
        $this->assertSame($results->message, "Unable to prepare: Table 'test.badtable' doesn't exist");
    }

    public function testCountOnehundoOnlyNegitive()
    {
        $whereConfig = [
            "fields" => ["id"],
            "values" => [0],
            "matches" => ["<"],
            "types" => ["i"]
        ];
        $results = $this->sql->basicCountV2("counttoonehundo", $whereConfig);
        $this->assertSame($results->status, true);
        $this->assertSame($results->items, 0);
        $this->assertSame($results->message, "ok");
    }

    public function testCountGroupped()
    {
        $results = $this->sql->groupCountV2("counttoonehundo", "cvalue");
        $this->assertSame($results->status, true);
        $this->assertSame($results->items, 10);
        $this->assertSame($results->message, "ok");
        $this->assertSame($results->dataset[0]["items"], 10);
    }

    public function testCountOnehundoOnlyIdsGtr60()
    {
        $whereConfig = [
            "fields" => ["id"],
            "values" => [60],
            "matches" => [">"],
            "types" => ["i"]
        ];
        $results = $this->sql->basicCountV2("counttoonehundo", $whereConfig);
        $this->assertSame($results->status, true);
        $this->assertSame($results->items, 40);
        $this->assertSame($results->message, "ok");
    }

    public function testRemoveHasEmptyedCheckCount()
    {
        $whereConfig = [
            "fields" => ["id"],
            "values" => [-1],
            "matches" => ["!="],
            "types" => ["i"]
        ];
        $results = $this->sql->basicCountV2("endoftestempty", $whereConfig);
        $this->assertSame($results->status, true);
        $this->assertSame($results->items, 0);
        $this->assertSame($results->message, "ok");
    }


    public function testAddHasAddedCheckCount()
    {
        $whereConfig = [
            "fields" => ["id"],
            "values" => [-1],
            "matches" => ["!="],
            "types" => ["i"]
        ];
        $results = $this->sql->basicCountV2("endoftestempty", $whereConfig);
        $this->assertSame($results->status, true);
        $this->assertSame($results->items, 0);
        $this->assertSame($results->message, "ok");
    }
}
