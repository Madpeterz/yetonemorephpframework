<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;

class MysqliSearchTest extends TestCase
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

    public function testSearchOk()
    {
        $tables = ["twintables1","twintables2"];
        $results = $this->sql->searchTables($tables, "title", "harry potter", "s", "=", 99, "id");
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($results->message, "ok");
        $this->assertSame($results->status, true);
        $this->assertSame($results->items, 2);
    }

    public function testSearchNoMatchs()
    {
        $tables = ["twintables1","twintables2"];
        $results = $this->sql->searchTables($tables, "message", "none", "s", "=", 99, "id");
        $this->assertSame($results->message, "ok");
        $this->assertSame($results->status, true);
        $this->assertSame($results->items, 0);
    }

    public function testSearchMissingField()
    {
        $tables = ["twintables1","twintables2"];
        $results = $this->sql->searchTables($tables, "notafield", "none", "s", "=", 99, "id");
        $this->assertSame($results->message, "Unable to prepare: Unknown column 'tb1.notafield' in 'where clause'");
        $this->assertSame($results->status, false);
        $this->assertSame($results->items, 0);
    }

    public function testSearchMissingTable()
    {
        $tables = ["notatable","twintables2"];
        $results = $this->sql->searchTables($tables, "title", "harry potter", "s", "=", 99, "id");
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($results->message, "Unable to prepare: Table 'test.notatable' doesn't exist");
        $this->assertSame($results->status, false);
        $this->assertSame($results->items, 0);
    }

    public function testSearchOnly1Table()
    {
        $tables = ["notatable"];
        $results = $this->sql->searchTables($tables, "title", "harry potter", "s", "=", 99, "id");
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($results->message, "Requires 2 or more tables to use search");
        $this->assertSame($results->status, false);
        $this->assertSame($results->items, 0);
    }

    public function testSearchEmptyMatchField()
    {
        $tables = ["notatable","twintables2"];
        $results = $this->sql->searchTables($tables, "", "harry potter", "s", "=", 99, "id");
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($results->message, "Requires a match field to be sent");
        $this->assertSame($results->status, false);
        $this->assertSame($results->items, 0);
    }

    public function testSearchInValidMatchSqlType()
    {
        $tables = ["notatable","twintables2"];
        $results = $this->sql->searchTables($tables, "title", "harry potter", "q", "=", 99, "id");
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($results->message, "Match type is not valid");
        $this->assertSame($results->status, false);
        $this->assertSame($results->items, 0);
    }

    public function testSearchIsNull()
    {
        $tables = ["twintables1","twintables2"];
        $results = $this->sql->searchTables($tables, "title", null, "s", "IS", 99, "id");
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($results->message, "ok");
        $this->assertSame($results->status, true);
        $this->assertSame($results->items, 0);
    }

    public function testSearchNullValueNoIs()
    {
        $tables = ["twintables1","twintables2"];
        $results = $this->sql->searchTables($tables, "title", null, "s", "=", 99, "id");
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($results->message, "Match value can not be null");
        $this->assertSame($results->status, false);
        $this->assertSame($results->items, 0);
    }

    public function testSearchNoSqlConnection()
    {
        $this->sql->shutdown();
        $this->sql->dbUser = "InValid";
        $this->sql->dbPass = null;
        $tables = ["twintables1","twintables2"];
        $results = $this->sql->searchTables($tables, "title", "title", "s", "=", 99, "id");
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($results->message, 'sqlStartConnection returned false!');
        $this->assertSame($results->status, false);
        $this->assertSame($results->items, 0);
    }
}
