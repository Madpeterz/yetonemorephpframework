<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\MySQLi\MysqliEnabled as MysqliConnector;
use YAPF\Generator\DbObjectsFactory as DbObjectsFactory;

class genTest extends TestCase
{
    /* @var YAPF\MySQLi\MysqliEnabled $sql */
    protected $sql = null;
    protected $db_objects_factory = null;
    protected function setUp(): void
    {
        define("GEN_DATABASE_HOST", "localhost");
        define("GEN_DATABASE_USERNAME", "testsuser");
        define("GEN_DATABASE_PASSWORD", "testsuserPW");
        define("GEN_ADD_DB_TO_TABLE", false); // add the database name before the table name
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

    public function test_reset_db_first()
    {
        $results = $this->sql->rawSQL("tests/testdataset.sql");
        // [status =>  bool, message =>  string]
        $this->assertSame($results["status"], true);
        $this->assertSame($results["message"], "51 commands run");
    }

    public function test_create_models()
    {
        $this->db_objects_factory = new DbObjectsFactory(false);
        $this->db_objects_factory->reconnectSql($this->sql);
        $this->db_objects_factory->noOutput();
        $this->db_objects_factory->start();
        $this->assertSame($this->db_objects_factory->getModelsCreated(), 20);
        $this->assertSame($this->db_objects_factory->getModelsFailed(), 0);
    }
}
