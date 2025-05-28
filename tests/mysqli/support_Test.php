<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\Helpers\FunctionHelper;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;

class MysqliSupportTest extends TestCase
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

    public function testLastSql()
    {
        $helper = new sha256Helper();
        $this->assertSame($this->sql->getLastSQl(), "");
        $config = [
            "table" => "endoftestwithfourentrys",
            "fields" => ["value"],
            "values" => [$helper->getSha256("testAdd888")],
            "types" => ["s"]
        ];
        $this->sql->addV2($config);
        $this->assertSame($this->sql->getLastSQl(), "INSERT INTO endoftestwithfourentrys (value) VALUES (?)");
    }

    public function testAddv2MissingKey()
    {
        $result = $this->sql->addV2();
        $this->assertSame($result->message, "Required key: table is missing");
        $this->assertSame($result->status, false);
        $this->assertSame($result->newId, null);
    }

    public function testAddv2IncorrectFieldstoValues()
    {
        $helper = new sha256Helper();
        $config = [
            "table" => "endoftestwithfourentrys",
            "fields" => ["value", "asdasda"],
            "values" => [$helper->getSha256("testAdd888")],
            "types" => ["s"]
        ];
        $result = $this->sql->addV2($config);
        $this->assertSame($result->message, "fields and values counts do not match!");
        $this->assertSame($result->status, false);
        $this->assertSame($result->newId, null);
    }
    public function testAddv2IncorrectValuesToTypes()
    {
        $helper = new sha256Helper();
        $config = [
            "table" => "endoftestwithfourentrys",
            "fields" => ["value"],
            "values" => [$helper->getSha256("testAdd888")],
            "types" => ["s", "asdasda"]
        ];
        $result = $this->sql->addV2($config);
        $this->assertSame($result->message, "values and types counts do not match!");
        $this->assertSame($result->status, false);
        $this->assertSame($result->newId, null);
    }

    public function testAddv2SqlStartupError()
    {
        $helper = new sha256Helper();
        $this->sql->sqlSave(true);
        $this->sql->dbName = "InValid";
        $config = [
            "table" => "endoftestwithfourentrys",
            "fields" => ["value"],
            "values" => [$helper->getSha256("testAdd888")],
            "types" => ["s"]
        ];
        $result = $this->sql->addV2($config);
        $this->assertSame($result->message, 'sqlStartConnection returned false!');
        $this->assertSame($result->status, false);
        $this->assertSame($result->newId, null);
    }

    public function testMysqliCoreDestruct()
    {
        $startup = $this->sql->sqlStart(false);
        $this->assertSame($startup, true);
        $result = $this->sql->shutdown();
        $this->assertSame(true, $result);
        $this->assertSame($this->sql->getLastErrorBasic(), "No changes made");
        $result = $this->sql->shutdown();
        $this->assertSame(true, $result);
        $this->assertSame($this->sql->getLastErrorBasic(), "Not connected");
    }

    public function testMysqliRawSql()
    {
        // comments only
        $result = $this->sql->rawSQL("tests/mysqli/testRawSQL_Commentsonly.sql");
        $this->assertSame($this->sql->getLastSQl(), "");
        $this->assertSame($this->sql->getLastErrorBasic(), "No commands processed from file");
        $this->assertSame(false, $this->sql->getNeedsCommit(), "The Commit flag is incorrectly set!");
        $this->assertSame($result->status, false);
        $this->sql->sqlSave(true);
        // missing ; on end
        $this->assertSame(false, $this->sql->getNeedsCommit(), "The Commit flag is incorrectly set!");
        $result = $this->sql->rawSQL("tests/mysqli/testRawSQL_Noending.sql");
        $this->assertSame($this->sql->getLastErrorBasic(), "Warning: raw sql has no ending ;");
        $this->assertSame(true, $result->status, "this should give a warning but still run");
        $this->assertSame(true, $this->sql->getNeedsCommit(), "The Commit flag is incorrectly set!");
        $this->sql->sqlSave(true);
        // empty
        $result = $this->sql->rawSQL("tests/mysqli/testRawSQL_Empty.sql");
        $this->assertSame($this->sql->getLastErrorBasic(), "File is empty");
        $this->assertSame(false, $result->status, "This has failed");
        $this->assertSame(false, $this->sql->getNeedsCommit(), "The Commit flag is incorrectly set!");
        $this->sql->sqlSave(true);
        // very broken
        $result = $this->sql->rawSQL("tests/mysqli/testRawSQL_Malformed.sql");
        $error_msg = "You have an error in your SQL syntax; check the manual ";
        $error_msg .= "that corresponds to your MariaDB server version for the right ";
        $error_msg .= "syntax to use near 'WHERE id != 4' at line 1";
        if (strpos($this->sql->getLastErrorBasic(), "MariaDB") === false) {
            $error_msg = strtr($error_msg, ["MariaDB" => "MySQL"]);
        }
        $this->assertSame($this->sql->getLastErrorBasic(), $error_msg);
        $this->assertSame($result->status, false);
        $this->assertSame(false, $this->sql->getNeedsCommit(), "The Commit flag is incorrectly set!");
        $this->sql->sqlSave(true);
        // no SQL connection
        $this->sql->dbName = "InValid";
        $result = $this->sql->rawSQL("tests/mysqli/testRawSQL_Noending.sql");
        $this->assertSame($this->sql->getLastErrorBasic(), 'sqlStartConnection returned false!');
        $this->assertSame($result->status, false);
        $this->assertSame(false, $this->sql->getNeedsCommit(), "The Commit flag is incorrectly set!");
    }

    public function testMysqliCountNoData()
    {
        $whereConfig = [
            "fields" => ["id"],
            "values" => [-1],
            "types" => ["i"],
            "matches" => ["<="],
        ];
        $result = $this->sql->basicCountV2("alltypestable", $whereConfig);
        $this->assertSame($result->items, 0);
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->status, true);
    }

    public function testFlagErrorRollback()
    {
        $results = $this->sql->basicCountV2("rollbacktest");
        $this->assertSame($results->items, 0);
        $this->assertSame($results->status, true);
        $config = [
            "table" => "rollbacktest",
            "fields" => ["name", "value"],
            "values" => ["kilme", 12],
            "types" => ["s", "i"],
        ];
        $results = $this->sql->addV2($config);
        $this->assertSame($results->status, true);
        $this->assertSame($results->message, "ok");
        $this->assertSame($results->newId, 1);
        $this->sql->flagError();
        $this->sql->sqlSave(); // reject save due to error and rollback
        $this->assertSame(
            "starting rollback",
            $this->sql->getLastErrorBasic(),
            "Sql status not as expected"
        );
        $results = $this->sql->basicCountV2("rollbacktest");
        $this->assertSame($results->items, 0);
        $this->assertSame($results->status, true);
        $results = $this->sql->addV2($config);
        $this->assertSame($results->status, true);
        $this->assertSame($results->message, "ok");
        $this->assertSame($results->newId, 2);
        $this->sql->sqlRollBack(); // force a rollback now
        $results = $this->sql->basicCountV2("rollbacktest");
        $this->assertSame($results->items, 0);
        $this->assertSame($results->status, true);
    }

    public function testConnectOtherhost()
    {
        // bad host / bad details / bad db
        $result = $this->sql->sqlStartConnection("testsuser", "testsuserPW", "test", true, "magicmadpeter.xyz", 1);
        $this->assertSame($result, false);
        $this->assertSame($this->sql->getLastErrorBasic(), 'Connect attempt died in a fire');
        // good host / bad details / good db
        $result = $this->sql->sqlStartConnection("fakeuser", "fakepassword", "test", true, "127.0.0.1", 1);
        $this->assertSame($result, false);
        $this->assertSame($this->sql->getLastErrorBasic(), 'Connect attempt died in a fire');
        // good host / good details / bad DB
        $this->sql->fullSqlErrors = true;
        $result = $this->sql->sqlStartConnection("testsuser", "testsuserPW", "fakedbname", true, "127.0.0.1", 1);
        $this->assertSame($result, false);
        $error_msg = "SQL connection error: mysqli_real_connect(): (HY000/1045): Access denied for user 'testsuser'@'localhost' (using password: YES)";
        $this->assertSame($error_msg, $this->sql->getLastErrorBasic(), "Wrong error message");
        // good host / good details / good DB
        $this->sql->fullSqlErrors = false;
        $result = $this->sql->sqlStartConnection("root", "", "information_schema", true);
        $this->assertSame(true, $result);
    }

    public function testSqlStartBadConfig()
    {
        $savedbuser = $this->sql->dbUser;
        $this->sql->dbUser = null;
        $result = $this->sql->sqlStart(true);
        $this->assertSame($this->sql->getLastErrorBasic(), "DB config is not valid to start!");
        $this->assertSame($result, false);
        $this->sql->dbUser = $savedbuser;
        $this->sql->dbPass = "Bad password";
        $result = $this->sql->sqlStart(true);
        $this->assertSame($this->sql->getLastErrorBasic(), 'sqlStartConnection returned false!');
        $this->assertSame($result, false);
    }

    public function testSqlSelectBadBinds()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["cvalue"],
            "values" => [256],
            "types" => ["tttt"],
            "matches" => ["<"],
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertStringContainsString("Where config failed: index: 0 is not as we expect", $result->message, "sql bind issue");
        $this->assertSame($result->status, false);
        $this->sql->fullSqlErrors = true;
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertStringContainsString("Where config failed: index: 0 is not as we expect:", $result->message, "sql bind issue");
        $this->assertSame($result->status, false);
    }

    public function testSqlSelectEmptyWhereConfig()
    {
        $result = $this->sql->selectV2(["table" => "example"], null, []);
        $this->assertSame($result->message, "Where config failed: whereConfig is empty but not null!");
        $this->assertSame($result->status, false);
    }
    public function testSqlSelectWhereConfigMissingKeys()
    {
        $result = $this->sql->selectV2(["table" => "example"], null, ["fields" => ["lol"]]);
        $this->assertSame($result->message, "Where config failed: missing where keys:values,types,matches");
        $this->assertSame($result->status, false);
    }

    public function testSelectWhereConfigFieldsToValueError()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["id"],
            "values" => [14, 44],
            "types" => ["s"],
            "matches" => ["="]
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "Where config failed: count error fields <=> values");
        $this->assertSame($result->items, 0);
        $this->assertSame($result->status, false);
    }

    public function testSelectWhereConfigValueToTypeError()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["id"],
            "values" => [14],
            "types" => ["s", "i"],
            "matches" => ["="]
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "Where config failed: count error values <=> types");
        $this->assertSame($result->items, 0);
        $this->assertSame($result->status, false);
    }

    public function testSelectWhereConfigTypetoMatchsError()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["id"],
            "values" => [14],
            "types" => ["s"],
            "matches" => ["=", "<="]
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "Where config failed: count error types <=> matches");
        $this->assertSame($result->items, 0);
        $this->assertSame($result->status, false);
    }

    public function testSelectWhereConfigExtraJoinWiths()
    {
        $basic_config = ["table" => "counttoonehundo"];
        $whereConfig = [
            "fields" => ["id"],
            "values" => [14],
            "types" => ["s"],
            "matches" => ["="],
            "joinWith" => ["OR", "AND"]
        ];
        $result = $this->sql->selectV2($basic_config, null, $whereConfig);
        // [dataset => mixed[mixed[]], status => bool, message => string]
        $this->assertSame($result->message, "Where config failed: whereConfig joinWith count error");
        $this->assertSame($result->items, 0);
        $this->assertSame($result->status, false);
    }
}
