<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;

class MysqliUpdateTest extends TestCase
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

    public function testUpdate()
    {
        $whereConfig = [
            "fields" => ["id"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["i"],
        ];
        $updateConfig = [
            "fields" => ["username"],
            "values" => ["NotMadpeter"],
            "types" => ["s"],
        ];
        $results = $this->sql->updateV2("endoftestwithupdates", $updateConfig, $whereConfig);
        // [changes => int, status => bool, message => string]
        $this->assertSame("ok", $results->message, "Incorrect update status message: ".$this->sql->getLastSql());
        $this->assertSame($results->status, true);
        $this->assertSame($results->itemsUpdated, 1);
        
    }

    public function testUpdateNoTypes()
    {
        $whereConfig = [
            "fields" => ["id"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["i"],
        ];
        $updateConfig = [
            "fields" => ["username"],
            "values" => ["NotMadpeter"],
            "types" => [],
        ];
        $results = $this->sql->updateV2("endoftestwithupdates", $updateConfig, $whereConfig);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results->status, false);
        $this->assertSame($results->itemsUpdated, 0);
        $this->assertSame($results->message, "No types given for update");
    }

    public function testUpdateBadUpdateConfigs()
    {
        $whereConfig = [
            "fields" => ["id"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["i"],
        ];
        $updateConfig = [
            "fields" => ["username"],
            "values" => [],
            "types" => ["s"],
        ];
        $results = $this->sql->updateV2("endoftestwithupdates", $updateConfig, $whereConfig);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results->status, false);
        $this->assertSame($results->itemsUpdated, 0);
        $this->assertSame($results->message, "count issue fields <=> values");
        $whereConfig = [
            "fields" => ["id"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["i"],
        ];
        $updateConfig = [
            "fields" => ["username"],
            "values" => ["lol"],
            "types" => ["s","i"],
        ];
        $results = $this->sql->updateV2("endoftestwithupdates", $updateConfig, $whereConfig);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results->status, false);
        $this->assertSame($results->itemsUpdated, 0);
        $this->assertSame($results->message, "count issue values <=> types");
    }

    public function testUpdateInValidTable()
    {
        $whereConfig = [
            "fields" => ["id"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["i"],
        ];
        $updateConfig = [
            "fields" => ["username"],
            "values" => ["NotMadpeter"],
            "types" => ["s"],
        ];
        $results = $this->sql->updateV2("badtable", $updateConfig, $whereConfig);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results->status, false);
        $this->assertSame($results->itemsUpdated, 0);
        $this->assertSame($results->message, "Unable to prepare: Table 'test.badtable' doesn't exist");

        $results = $this->sql->updateV2("", $updateConfig, $whereConfig);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results->status, false);
        $this->assertSame($results->itemsUpdated, 0);
        $this->assertSame($results->message, "No table given");
    }

    public function testUpdateInValidField()
    {
        $whereConfig = [
            "fields" => ["missingfield"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["s"]
        ];
        $updateConfig = [
            "fields" => ["username"],
            "values" => ["NotMadpeter"],
            "types" => ["s"]
        ];
        $results = $this->sql->updateV2("endoftestwithupdates", $updateConfig, $whereConfig);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results->status, false);
        $this->assertSame($results->itemsUpdated, 0);
        $this->assertStringContainsString("Unable to prepare: Unknown column 'missingfield'", $results->message);
    }

    public function testUpdateInValidValue()
    {
        $whereConfig = [
            "fields" => ["id"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["i"]
        ];
        $updateConfig = [
            "fields" => ["username"],
            "values" => [null],
            "types" => ["s"]
        ];
        $results = $this->sql->updateV2("endoftestwithupdates", $updateConfig, $whereConfig);
        $this->assertSame("Unable to execute because: Column 'username' cannot be null", $results->message,
        "Your mysql server is not setup in strict mode\n 
change sql_mode=NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION to STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER");
        $this->assertSame(false, $results->status);
        $this->assertSame(0, $results->itemsUpdated);
    }

    public function testUpdateMultiple()
    {
        $whereConfig = [
            "fields" => ["id"],
            "values" => [-1],
            "matches" => ["!="],
            "types" => ["i"]
        ];
        $updateConfig = [
            "fields" => ["value"],
            "values" => ["magic"],
            "types" => ["s"]
        ];
        $results = $this->sql->updateV2("liketests", $updateConfig, $whereConfig);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results->status, true);
        $this->assertSame($results->itemsUpdated, 2);
        $this->assertSame($results->message, "ok");
    }

    public function testUpdateLike()
    {
        $whereConfig = [
            "fields" => ["name"],
            "values" => ["Advent"],
            "matches" => ["% LIKE %"],
            "types" => ["s"]
        ];
        $updateConfig = [
            "fields" => ["value"],
            "values" => ["woof"],
            "types" => ["s"]
        ];
        $results = $this->sql->updateV2("liketests", $updateConfig, $whereConfig);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results->status, true);
        $this->assertSame($results->itemsUpdated, 2);
        $this->assertSame($results->message, "ok");
    }

    public function testUpdateNoSqlConnection()
    {
        $this->sql->sqlSave();
        $this->sql->dbUser = "InValid";
        $this->sql->dbPass = null;
        $whereConfig = [
            "fields" => ["id"],
            "values" => [1],
            "matches" => ["="],
            "types" => ["i"],
        ];
        $updateConfig = [
            "fields" => ["username"],
            "values" => ["NotMadpeter"],
            "types" => ["s"],
        ];
        $results = $this->sql->updateV2("endoftestwithupdates", $updateConfig, $whereConfig);
        // [changes => int, status => bool, message => string]
        $this->assertSame($results->status, false);
        $this->assertSame($results->itemsUpdated, 0);
        $this->assertSame($results->message, 'sqlStartConnection returned false!');
    }
}
