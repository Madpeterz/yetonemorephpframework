<?php

namespace JUNK;

use PHPUnit\Framework\TestCase;
use YAPF\MySQLi\MysqliEnabled as MysqliConnector;

$sql = null;
class DbObjectsLoadTest extends TestCase
{
    /* @var YAPF\MySQLi\MysqliEnabled $sql */
    protected $sql = null;
    protected function setUp(): void
    {
        global $sql;
        define("REQUIRE_ID_ON_LOAD", true);
        $sql = new MysqliConnector();
    }
    protected function tearDown(): void
    {
        global $sql;
        $sql->sqlSave(true);
        $sql = null;
    }
    public function test_reset_db_first()
    {
        global $sql;
        $results = $sql->rawSQL("tests/testdataset.sql");
        // [status =>  bool, message =>  string]
        $this->assertSame($results["status"], true);
        $this->assertSame($results["message"], "51 commands run");
    }
    public function testLoadId()
    {
        $countto = new Counttoonehundo();
        $load_status = $countto->loadID(44);
        $this->assertSame($load_status, true);
        $this->assertSame($countto->getId(), 44);
        $this->assertSame($countto->getCvalue(), 8);
    }

    public function testLoadSet()
    {
        $countto = new CounttoonehundoSet();
        $load_status = $countto->loadAll();
        $this->assertSame($load_status["status"], true);
        $this->assertSame($load_status["count"], 100);
        $this->assertSame($load_status["message"], "ok");
    }

    public function testLoadRange()
    {
        $countto = new CounttoonehundoSet();
        $load_status = $countto->loadLimited(44, "id", "DESC", [], [], "AND", 1);
        $this->assertSame($load_status["status"], true);
        $this->assertSame($load_status["count"], 44);
        $this->assertSame($load_status["message"], "ok");
        $firstobj = $countto->getFirst();
        $this->assertSame($firstobj->getId(), 56);
        $this->assertSame($firstobj->getCvalue(), 32);
    }

    public function testLoadNewest()
    {
        $countto = new CounttoonehundoSet();
        $load_status = $countto->loadNewest(5);
        $this->assertSame($load_status["status"], true);
        $this->assertSame($load_status["count"], 5);
        $this->assertSame($load_status["message"], "ok");
        $firstobj = $countto->getFirst();
        $this->assertSame($firstobj->getId(), 100);
        $this->assertSame($firstobj->getCvalue(), 512);
    }

    public function testLoadWithConfig()
    {
        $countto = new Counttoonehundo();
        $where_config = [
            "fields" => ["cvalue","id"],
            "values" => [257,91],
            "types" => ["i","i"],
            "matches" => [">=",">="],
        ];
        $load_status = $countto->loadWithConfig($where_config);
        $this->assertSame($load_status, true);
        $this->assertSame($countto->getId(), 100);
    }

    public function testLoadNothing()
    {
        $twintables1 = new Twintables1Set();
        $where_config = [
            "fields" => ["id"],
            "values" => [0],
            "types" => ["i"],
            "matches" => ["<"],
        ];
        $load_status = $twintables1->loadWithConfig($where_config);
        $this->assertSame($load_status["status"], true);
        $this->assertSame($load_status["count"], 0);
    }
}
