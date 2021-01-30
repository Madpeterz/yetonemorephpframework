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
        global $GEN_DATABASE_HOST, $GEN_DATABASE_USERNAME, $GEN_DATABASE_PASSWORD;
        global $GEN_ADD_DB_TO_TABLE, $GEN_SAVE_MODELS_TO, $GEN_DATABASES, $GEN_NAMESPACE_SINGLE;
        global $GEN_NAMESPACE_SET, $GEN_SAVE_SET_MODELS_TO, $GEN_SELECTED_TABLES_ONLY;
        
        $GEN_DATABASE_HOST = "localhost";
        $GEN_DATABASE_USERNAME = "testuser";
        $GEN_DATABASE_PASSWORD = "testsuserPW";
        $GEN_ADD_DB_TO_TABLE = true;
        $GEN_SAVE_MODELS_TO = "src/Junk/Models/";
        $GEN_SAVE_SET_MODELS_TO = "src/Junk/Sets/";
        $GEN_SELECTED_TABLES_ONLY = null;

        $GEN_DATABASES = ["test"];
        $GEN_NAMESPACE_SINGLE = "YAPF\Junk\Models";
        $GEN_NAMESPACE_SET = "YAPF\Junk\Sets";

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
        $this->assertSame($results["message"], "65 commands run");
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
