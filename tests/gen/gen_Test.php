<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;
use YAPF\Framework\Generator\DbObjectsFactory as DbObjectsFactory;

class GeneratorTest extends TestCase
{
    /* @var YAPF\Framework\MySQLi\MysqliEnabled $sql */
    protected ?MysqliConnector $sql;
    protected $db_objects_factory = null;
    protected function setUp(): void
    {
        global $GEN_DATABASE_HOST, $GEN_DATABASE_USERNAME, $GEN_DATABASE_PASSWORD;
        global $GEN_PREFIX_TABLE, $GEN_SOLO_NS, $GEN_DATABASES, $GEN_SOLO_PATH;
        global $GEN_SET_NS, $GEN_SET_PATH, $GEN_TABLES_ARRAY;

        $GEN_DATABASE_HOST = "localhost";
        $GEN_DATABASE_USERNAME = "testuser";
        $GEN_DATABASE_PASSWORD = "testsuserPW";
        $GEN_PREFIX_TABLE = true;
        $GEN_SOLO_PATH = "src/Junk/Models/";
        $GEN_SET_PATH = "src/Junk/Sets/";
        $GEN_TABLES_ARRAY = null;

        $GEN_DATABASES = ["test"];
        $GEN_SOLO_NS = "YAPF\Junk\Models";
        $GEN_SET_NS = "YAPF\Junk\Sets";

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
        $this->assertSame($results->status, true);
        $this->assertSame($results->commandsRun, 56);
    }

    public function testCreateModels()
    {
        $this->db_objects_factory = new DbObjectsFactory(false, true);
        $this->db_objects_factory->reconnectSql($this->sql);
        $this->db_objects_factory->useTabs();
        $this->db_objects_factory->noOutput();
        $this->db_objects_factory->start();
        $this->assertSame($this->db_objects_factory->getLastErrorBasic(), "");
        $this->assertSame($this->db_objects_factory->getModelsFailed(), 0);
        $this->assertSame($this->db_objects_factory->getModelsCreated(), 26);
        $this->assertSame($this->db_objects_factory->getTotalRelatedActions(), 4);
        $this->assertSame($this->db_objects_factory->getOutput(), "");
    }

    public function testCreateModelsWithOutputAutoStart()
    {
        global $sql;
        $sql = $this->sql;
        $this->db_objects_factory = new DbObjectsFactory(false);
        $this->db_objects_factory->setOutputToHTML();
        $this->db_objects_factory->start();
        $this->assertSame($this->db_objects_factory->getLastErrorBasic(), "");
        $this->assertSame($this->db_objects_factory->getModelsFailed(), 0);
        $this->assertSame($this->db_objects_factory->getModelsCreated(), 26);
        $this->assertSame($this->db_objects_factory->getTotalRelatedActions(), 4);
        $this->assertGreaterThan(0, strlen($this->db_objects_factory->getOutput()));
    }

    public function testUserUnableToReadschema()
    {
        $this->sql->sqlSave(true);
        $this->sql->dbUser = "test2";
        $this->db_objects_factory = new DbObjectsFactory(false);
        $this->db_objects_factory->setOutputToHTML();
        $this->db_objects_factory->reconnectSql($this->sql);
        $this->db_objects_factory->start();
        $this->assertSame($this->db_objects_factory->getLastErrorBasic(), "Error ~ Unable to get tables for test");
        $this->assertSame($this->db_objects_factory->getModelsFailed(), 0);
        $this->assertSame($this->db_objects_factory->getModelsCreated(), 0);
    }
}
