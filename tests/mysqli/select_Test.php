<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;

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
        $this->assertSame($results->status, true);
        $this->assertSame($results->commandsRun, 57);
    }

    public function testSelectBasic()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $result = $this->sql->selectV2($basic_config);
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->items, 100);
        $this->assertSame($result->status, true);
    }

    public function testSelectNoTable()
    {
        $basic_config = [];
        $result = $this->sql->selectV2($basic_config);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "table index missing from basic config!");
        $this->assertSame($result->items, 0);
        $this->assertSame($result->status, false);
    }

    public function testSelectEmptyTable()
    {
        $basic_config = ["table" => ""];
        $result = $this->sql->selectV2($basic_config);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "No table set in basic config!");
        $this->assertSame($result->items, 0);
        $this->assertSame($result->status, false);
    }

    public function testSelectSimpleLimit()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $options = ["limit" => 5];
        $result = $this->sql->selectV2($basic_config, null, null, $options);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->items, 5);
        $this->assertSame($result->status, true);
    }

    public function testSelectEmptyInArray()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["id"],
            "values" => [[]],
            "types" => ["i"],
            "matches" => ["IN"]
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "Targeting IN|NOT IN with no array");
        $this->assertSame($result->items, 0);
        $this->assertSame($result->status, false);
    }

    public function testSelectNullAsText()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $options = ["limit" => 5];
        $whereConfig = [
            "fields" => ["id"],
            "values" => ["null"],
            "types" => ["i"],
            "matches" => ["IS NOT"]
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig, $options);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->items, 5);
        $this->assertSame($result->status, true);
    }

    public function testSelectUnkownWhereMatcher()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["id"],
            "values" => [4],
            "types" => ["i"],
            "matches" => ["lol"]
        ];
        $this->sql->fullSqlErrors = false;
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "Where config failed: Unsupported where match type!");
        $this->assertSame($result->items, 0);
        $this->assertSame($result->status, false);
    }

    public function testSelectIsNotNull()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["cvalue"],
            "values" => [null],
            "types" => ["i"],
            "matches" => ["IS NOT"]
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->items, 100);
        $this->assertSame($result->status, true);
    }

    public function testSelectInNotArray()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["cvalue"],
            "values" => ["magic"],
            "types" => ["i"],
            "matches" => ["IN"]
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "Targeting IN|NOT IN with no array");
        $this->assertSame($result->items, 0);
        $this->assertSame($result->status, false);
    }

    public function testSelectGroupedMatches()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["cvalue", "cvalue", "cvalue"],
            "values" => [1, 2560, 100],
            "types" => ["i", "i", "i"],
            "matches" => ["=", "!=", "<"],
            "joinWith" => [") OR", "AND"]
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $expected_sql = "SELECT * FROM counttoonehundo  WHERE (`cvalue` = ? OR `cvalue` != ?) AND `cvalue` < ?";
        $this->assertSame($this->sql->getLastSql(), $expected_sql);
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->items, 70);
        $this->assertSame($result->status, true);
    }

    public function testSelectGroupedMatchesOpenFirst()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["cvalue", "cvalue", "cvalue"],
            "values" => [1, 2560, 100],
            "types" => ["i", "i", "i"],
            "matches" => ["=", "!=", "<"],
            "joinWith" => ["( OR", "AND"]
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $expected_sql = "SELECT * FROM counttoonehundo  WHERE (`cvalue` = ? OR (`cvalue` != ? AND `cvalue` < ?))";
        $this->assertSame($this->sql->getLastSql(), $expected_sql);
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->items, 70);
        $this->assertSame($result->status, true);
    }

    public function testSelectGroupedEmptyInArray()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["cvalue", "cvalue", "cvalue"],
            "values" => [1, 2560, []],
            "types" => ["i", "i", "i"],
            "matches" => ["=", "!=", "IN"],
            "joinWith" => ["( OR", "AND"]
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "Targeting IN|NOT IN with no array");
        $this->assertSame($result->items, 0);
        $this->assertSame($result->status, false);
    }

    public function testSelectGroupedUnknownMatcher()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["cvalue", "cvalue", "cvalue"],
            "values" => [1, 2560, 44],
            "types" => ["i", "i", "i"],
            "matches" => ["LOL", "!=", "="],
            "joinWith" => ["( OR", "AND"]
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "Where config failed: Unsupported where match type!");
        $this->assertSame($result->items, 0);
        $this->assertSame($result->status, false);
    }

    public function testSelectBasicExtended()
    {
        $basic_config = [
            "table" => "counttoonehundo",
            "fields" => ["SUM(cvalue) as total", "count(id) as items"],
        ];
        $result = $this->sql->selectV2($basic_config);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->items, 1);
        $this->assertSame($result->status, true);
        $this->assertSame($result->dataset[0]["total"], '10230');
        $this->assertSame($result->dataset[0]["items"], 100);
    }

    public function testSelectOrdering()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $order_config = [
            "enabled" => true,
            "byField" => "id",
            "dir" => "DESC"
        ];
        $result = $this->sql->selectV2($basic_config, $order_config);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->items, 100);
        $this->assertSame($result->status, true);
        $this->assertSame($result->dataset[0]["id"], 100);
        $this->assertSame($result->dataset[0]["cvalue"], 512);
        $basic_config = ["table" => "counttoonehundo"];
        $order_config = [
            "enabled" => true,
        ];
        $result = $this->sql->selectV2($basic_config, $order_config);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->items, 100);
        $this->assertSame($result->status, true);
        $this->assertSame($result->dataset[0]["id"], 100);
        $this->assertSame($result->dataset[0]["cvalue"], 512);
        $basic_config = ["table" => "counttoonehundo"];
        $order_config = [
            "enabled" => true,
            "as_string" => " id DESC"
        ];
        $result = $this->sql->selectV2($basic_config, $order_config);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->items, 100);
        $this->assertSame($result->status, true);
        $this->assertSame($result->dataset[0]["id"], 100);
        $this->assertSame($result->dataset[0]["cvalue"], 512);
    }



    public function testSelectWhereConfig()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["cvalue"],
            "values" => [256],
            "types" => ["i"],
            "matches" => ["<"],
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->items, 80);
        $this->assertSame($result->status, true);
        $this->assertSame($result->dataset[0]["id"], 1);
        $this->assertSame($result->dataset[0]["cvalue"], 1);
    }

    public function testSelectLeftJoin()
    {
        $basic_config = [
            "table" => "relationtestinga",
            "fields" => ["mtb.id", "mtb.name", "tw2.extended1", "tw2.extended2", "tw2.extended3"],
        ];
        $joinTables = [
            "mainTableId" => "mtb",
            "types" => ["LEFT JOIN"],
            "tables" => ["relationtestingb tw2"],
            "onFieldLeft" => ["tw2.id"],
            "onFieldMatch" => ["="],
            "onFieldRight" => ["mtb.linkid"],
        ];
        $order_config = [
            "enabled" => true,
            "byField" => "id",
            "dir" => "DESC"
        ];
        $result = $this->sql->selectV2($basic_config, $order_config, null, null, $joinTables);
        $this->assertSame($result->message, "ok");
        $expected_sql = "SELECT mtb.id, mtb.name, tw2.extended1, tw2.extended2, tw2.extended3 FROM ";
        $expected_sql .= "relationtestinga mtb LEFT JOIN relationtestingb tw2 ON tw2.id = mtb.linkid ORDER BY id DESC";
        $this->assertSame($this->sql->getLastSql(), $expected_sql);
        $this->assertSame($result->status, true);
        $this->assertSame($result->items, 2);
        $this->assertSame($result->dataset[0]["name"], "group2");
        $this->assertSame($result->dataset[0]["extended1"], "c1");
    }

    public function testSelectInnerJoin()
    {
        $basic_config = [
            "table" => "twintables1",
            "fields" => ["mtb.id", "mtb.message as table1message", "tw2.message as table2message"],
        ];
        $joinTables = [
            "mainTableId" => "mtb",
            "cleanIds" => true,
            "types" => ["JOIN"],
            "tables" => ["twintables2 tw2"],
            "onFieldLeft" => [""],
            "onFieldMatch" => [""],
            "onFieldRight" => [""],
        ];
        $order_config = [
            "enabled" => true,
            "byField" => "id",
            "dir" => "DESC"
        ];
        $result = $this->sql->selectV2($basic_config, $order_config, null, null, $joinTables);
        $expected_sql = "SELECT mtb.id, mtb.message as table1message, tw2.message as table2message FROM ";
        $expected_sql .= "twintables1 mtb JOIN twintables2 tw2 ORDER BY id DESC";
        $this->assertSame($this->sql->getLastSql(), $expected_sql);
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->status, true);
        $this->assertSame($result->items, 1);
        $this->assertSame($result->dataset[0]["table1message"], "is not very good");
        $this->assertSame($result->dataset[0]["table2message"], "is great");
    }

    public function testSelecJoinBadJoinConfig()
    {
        $basic_config = [
            "table" => "twintables1",
            "fields" => ["mtb.id", "mtb.message as table1message", "tw2.message as table2message"],
        ];
        $joinTables = [
            "mainTableId" => "mtb",
            "cleanIds" => true,
            "types" => ["JOIN"],
            "tables" => ["twintables2 tw2"],
            "onFieldLeft" => [""],
            "onFieldMatch" => [""],
            "onFieldRight" => [],
        ];
        $order_config = [
            "enabled" => true,
            "byField" => "id",
            "dir" => "DESC"
        ];
        $result = $this->sql->selectV2($basic_config, $order_config, null, null, $joinTables);
        $this->assertSame($result->message, "failed with message:counts match error onFieldRight <=> onFieldMatch");
        $this->assertSame($result->status, false);
    }

    public function testSelectMissingJoinConfig()
    {
        $basic_config = [
            "table" => "relationtestinga",
            "fields" => ["mtb.id", "mtb.name", "tw2.extended1", "tw2.extended2", "tw2.extended3"],
        ];
        $joinTables = [
            "mainTableId" => "mtb",
            "types" => ["LEFT JOIN"],
            "tables" => ["relationtestingb tw2"],
            "onFieldLeft" => ["tw2.id"],
            "onFieldRight" => ["mtb.linkid"],
        ];
        $order_config = [
            "enabled" => true,
            "byField" => "id",
            "dir" => "DESC"
        ];
        $result = $this->sql->selectV2($basic_config, $order_config, null, null, $joinTables);
        $this->assertSame($result->message, "Unable to prepare: Unknown column 'tw2.extended1' in 'field list'");
        $this->assertSame($result->status, false);
    }

    public function testSelectNoSqlConnection()
    {
        $this->sql->sqlSave();
        $this->sql->dbUser = "InValid";
        $this->sql->dbPass = null;
        $basic_config = ["table" => "counttoonehundo"];
        $result = $this->sql->selectV2($basic_config);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "sqlStartConnection returned false!");
        $this->assertSame($result->items, 0);
        $this->assertSame($result->status, false);
    }

    public function testSelectWithFunctionWhere()
    {
        $basic_config = ["table" => "relationtestinga"];
        $whereConfig = [
            "fields" => ["CHAR_LENGTH(name)", "linkid"],
            "values" => [3, 4],
            "types" => ["i", "i"],
            "matches" => [">=", "!="],
            "asFunction" => [1]
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->items, 1);
        $this->assertSame($result->status, true);
        $this->assertSame("group1", $result->dataset[0]["name"], "incorrect group value returned");
    }
}
