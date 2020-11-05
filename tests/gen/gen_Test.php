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
        define("GEN_SAVE_MODELS_TO", "junk/");
        define("GEN_DATABASES", ["test"]);
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

    public function test_junk_is_empty()
    {
        if (is_dir("junk") == false) {
            mkdir("junk");
        }
        if (is_dir("junk") == false) {
            $this->assertSame("junk", "is dir");
        } else {
            $files = glob('junk/*'); // get all file names
            foreach ($files as $file) { // iterate files
                if (is_file($file)) {
                    unlink($file); // delete file
                }
            }
            $this->assertSame("ok", "ok");
        }
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

    public function test_files_match_expected()
    {
        if (file_exists("junk/alltypestable.php") == true) {
            if (sha1_file("tests/example_model_output.php") == sha1_file("junk/alltypestable.php")) {
                $this->assertSame("pass", "pass");
            } else {
                $this->assertSame("file sha1", "failed");
            }
        } else {
            $this->assertSame("missing", "model output");
        }
    }
}
