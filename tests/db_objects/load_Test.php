<?php

namespace YAPF\Junk;

use PHPUnit\Framework\Constraint\Count;
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
    public function testResetDbFirst()
    {
        global $sql;
        $results = $sql->rawSQL("tests/testdataset.sql");
        // [status =>  bool, message =>  string]
        $this->assertSame($results["status"], true);
        $this->assertSame($results["message"], "65 commands run");
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
        $this->assertSame($load_status["message"], "ok");
        $this->assertSame($load_status["status"], true);
        $this->assertSame($load_status["count"], 100);
    }

    public function testLoadRange()
    {
        $countto = new CounttoonehundoSet();
        $load_status = $countto->loadLimited(44, "id", "DESC", [], [], "AND", 1);
        $this->assertSame($load_status["message"], "ok");
        $this->assertSame($load_status["status"], true);
        $this->assertSame($load_status["count"], 44);
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

    public function testLoadSingleLoadExtendedTests()
    {
        $countto = new Counttoonehundo();
        $result = $countto->loadOnField("id", 44);
        $this->assertSame($result, true);
        $countto = new Counttoonehundo();
        $countto->makedisabled();
        $result = $countto->loadOnField("id", 44);
        $this->assertSame($result, false);
        $weird = new Weirdtable();
        $result = $weird->loadOnField("weirdb", 3);
        $this->assertSame($weird->getId(), null);
        $this->assertSame($result, false);
        $countto = new Counttoonehundo();
        $result = $countto->loadByField("cvalue", 128);
        $this->assertSame($countto->getLastSql(), "SELECT * FROM test.counttoonehundo  WHERE cvalue = ?");
        $this->assertSame($countto->getLastErrorBasic(), "Load error incorrect number of entrys expected 1 but got:10");
        $this->assertSame($result, false);
    }

    public function testLoadByFieldInvaildField()
    {
        $countto = new Counttoonehundo();
        $result = $countto->loadByField("fake", 44);
        $this->assertSame($result, false);
        $this->assertSame($countto->getLastErrorBasic(), "Attempted to get field type: fake but its not supported!");
    }

    public function testLoadSetByIds()
    {
        $countto = new CounttoonehundoSet();
        $result = $countto->loadIds([1,2,3,4,5,6,7,8,9,19], "id", false);
        $this->assertSame($result["status"], true);
        $this->assertSame($result["count"], 10);
        $this->assertSame($result["message"], "ok");
        $result = $countto->loadIds([], "id", false);
        $this->assertSame($result["status"], false);
        $this->assertSame($result["count"], 0);
        $this->assertSame($result["message"], "No ids sent!");
    }

    public function testLoadSetloadOnFields()
    {
        $countto = new CounttoonehundoSet();
        $result = $countto->loadOnFields(["cvalue","id"], [10,50], [">=","<="]);
        $this->assertSame($result["status"], true);
        $this->assertSame($result["count"], 30);
        $this->assertSame($result["message"], "ok");
    }

    public function testLoadSetloadByField()
    {
        $countto = new CounttoonehundoSet();
        $result = $countto->loadByField("cvalue", 32);
        $this->assertSame($result["status"], true);
        $this->assertSame($result["count"], 10);
        $this->assertSame($result["message"], "ok");
        $testing = new WeirdtableSet();
        $result = $testing->loadAll();
        $this->assertSame($result["status"], true);
        $this->assertSame($result["count"], 2);
        $this->assertSame($result["message"], "ok");
    }

    public function testLoadSetWithConfigInvaild()
    {
        $countto = new CounttoonehundoSet();
        $where_config = [
            "fields" => [],
            "values" => [123],
            "types" => ["i"],
            "matches" => ["<="],
        ];
        $result = $countto->loadWithConfig($where_config);
        $this->assertSame($result["status"], false);
        $this->assertSame($result["count"], 0);
        $errormsg = "YAPF\Junk\CounttoonehundoSet Unable to load data: ";
        $errormsg .= "Where config failed: count error fields <=> values";
        $this->assertSame($result["message"], $errormsg);
    }

    public function testLoadOnFieldsMissingField()
    {
        $countto = new CounttoonehundoSet();
        $result = $countto->loadOnFields(["missing"], [1], ["="]);
        $this->assertSame($result["status"], false);
        $this->assertSame($result["count"], 0);
        $this->assertSame($result["message"], "getMissing is not supported on worker");
    }
}
