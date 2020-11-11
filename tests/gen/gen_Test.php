<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\MySQLi\MysqliEnabled as MysqliConnector;
use YAPF\Generator\DbObjectsFactory as DbObjectsFactory;

class GeneratorTest extends TestCase
{
    /* @var YAPF\MySQLi\MysqliEnabled $sql */
    protected ?MysqliConnector $sql;
    protected $db_objects_factory = null;
    protected function setUp(): void
    {
        define("GEN_DATABASE_HOST", "localhost");
        define("GEN_DATABASE_USERNAME", "testsuser");
        define("GEN_DATABASE_PASSWORD", "testsuserPW");
        define("GEN_ADD_DB_TO_TABLE", true); // add the database name before the table name
        define("GEN_SAVE_MODELS_TO", "src/Junk/");
        define("GEN_DATABASES", ["test"]);
        define("GEN_NAMESPACE", "YAPF\Junk");
        $this->sql = new MysqliConnector();
    }
    protected function tearDown(): void
    {
        $this->sql->sqlSave(true);
        $this->sql = null;
    }

    public function testResetDbFirst()
    {
        $results = $this->sql->rawSQL("tests/testdataset.sql");
        // [status =>  bool, message =>  string]
        $this->assertSame($results["status"], true);
        $this->assertSame($results["message"], "62 commands run");
    }

    public function testCreateModels()
    {
        $this->db_objects_factory = new DbObjectsFactory(false);
        $this->db_objects_factory->reconnectSql($this->sql);
        $this->db_objects_factory->useTabs();
        $this->db_objects_factory->noOutput();
        $this->db_objects_factory->start();
        $this->assertSame($this->db_objects_factory->getLastErrorBasic(), "");
        $this->assertSame($this->db_objects_factory->getModelsFailed(), 0);
        $this->assertSame($this->db_objects_factory->getModelsCreated(), 24);
        $this->assertSame($this->db_objects_factory->getOutput(), "");
    }

    public function testCreateModelsWithOutputAutoStart()
    {
        global $sql;
        $sql = $this->sql;
        $this->db_objects_factory = new DbObjectsFactory();
        $this->assertSame($this->db_objects_factory->getLastErrorBasic(), "");
        $this->assertSame($this->db_objects_factory->getModelsFailed(), 0);
        $this->assertSame($this->db_objects_factory->getModelsCreated(), 24);
        $this->assertGreaterThan(0, strlen($this->db_objects_factory->getOutput()));
    }

    public function testUserUnableToReadschema()
    {
        $this->sql->sqlSave(true);
        $this->sql->dbUser = "test2";
        $this->db_objects_factory = new DbObjectsFactory(false);
        $this->db_objects_factory->reconnectSql($this->sql);
        $this->db_objects_factory->start();
        $this->assertSame($this->db_objects_factory->getLastErrorBasic(), "Error ~ Unable to get tables for test");
        $this->assertSame($this->db_objects_factory->getModelsFailed(), 0);
        $this->assertSame($this->db_objects_factory->getModelsCreated(), 0);
    }
}
