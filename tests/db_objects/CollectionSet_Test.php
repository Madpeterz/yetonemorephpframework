<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\MySQLi\MysqliEnabled as MysqliConnector;
use YAPF\DbObjects\GenClass\GenClass as GenClass;
use YAPF\DbObjects\CollectionSet\CollectionSet as CollectionSet;

// Do not edit this file, rerun gen.php to update!
class BrokenDbObjectMassive extends genClass
{
    protected $use_table = "test.counttoonehundo";
    protected $dataset = [
        "lol" => ["type" => "mouse", "value" => null],
    ];
    public function getCvalue(): ?int
    {
        return $this->getField("cvalue");
    }
}
class BrokenDbObjectMassiveSet extends CollectionSet
{
}

$sql = null;
class CollectionSetTest extends TestCase
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
        $this->assertSame($results["message"], "62 commands run");
    }
    public function testPurgeCollectionSetEmpty()
    {
        $testing = new CounttoonehundoSet();
        $result = $testing->purgeCollection();
        $this->assertSame($result["message"], "Collection empty to start with");
        $this->assertSame($result["removed_entrys"], 0);
        $this->assertSame($result["status"], true);
    }
    public function testBulkUpdateSet()
    {
        $testing = new CounttoonehundoSet();
        $testing->loadAll();
        $result = $testing->updateFieldInCollection("cvalue", 55);
        $this->assertSame($result["message"], "ok");
        $this->assertSame($result["changes"], 100);
        $this->assertSame($result["status"], true);
    }
    public function testBulkUpdateNoChanges()
    {
        $testing = new CounttoonehundoSet();
        $testing->loadAll();
        $result = $testing->updateMultipleFieldsForCollection(["cvalue"], [55]);
        $this->assertSame($result["message"], "No changes made");
        $this->assertSame($result["changes"], 0);
        $this->assertSame($result["status"], true);
        $result = $testing->updateMultipleFieldsForCollection([], []);
        $this->assertSame($result["message"], "No fields being updated!");
        $this->assertSame($result["changes"], 0);
        $this->assertSame($result["status"], false);
    }
    public function testBulkUpdateInvaildField()
    {
        $testing = new CounttoonehundoSet();
        $testing->loadAll();
        $result = $testing->updateFieldInCollection("turndown4what", "shots");
        $this->assertSame($result["message"], "Unable to find getter: getTurndown4what");
        $this->assertSame($result["changes"], 0);
        $this->assertSame($result["status"], false);
    }
    public function testBulkUpdateUnknownFieldType()
    {
        $testing = new BrokenDbObjectMassiveSet();
        $testing->addToCollected(new BrokenDbObjectMassive());
        $result = $testing->updateFieldInCollection("cvalue", 421);
        $this->assertSame($result["message"], "Unable to find fieldtype: cvalue");
        $this->assertSame($result["changes"], 0);
        $this->assertSame($result["status"], false);
    }
    public function testGetCollection()
    {
        $testing = new CounttoonehundoSet();
        $testing->loadLimited(4);
        $result = $testing->getCollection();
        $this->assertSame(count($result), 4);
        $this->assertSame($result[0]->getCvalue(), 55);
    }
    public function testGetLinkedArray()
    {
        $testing = new Twintables1Set();
        $testing->loadAll();
        $result = $testing->getLinkedArray("id", "title");
        $this->assertSame($testing->getLastErrorBasic(), "");
        $this->assertSame(array_values($result)[0], "harry potter");
        $this->assertSame(array_keys($result)[0], 1);
        $result = $testing->getLinkedArray("id", "missing");
        $this->assertSame(count($result), 0);
        $this->assertSame($testing->getLastErrorBasic(), "Field: missing is missing");
        $result = $testing->getLinkedArray("missing", "id");
        $this->assertSame(count($result), 0);
        $this->assertSame($testing->getLastErrorBasic(), "Field: missing is missing");
    }
    public function testResetDbAgain()
    {
        global $sql;
        $results = $sql->rawSQL("tests/testdataset.sql");
        // [status =>  bool, message =>  string]
        $this->assertSame($results["status"], true);
        $this->assertSame($results["message"], "62 commands run");
    }
    public function testGetIdsMatchingField()
    {
        $countto = new CounttoonehundoSet();
        $countto->loadAll();
        $result = $countto->getIdsMatchingField("cvalue", 256);
        $this->assertSame(count($result), 10);
        $this->assertSame(in_array(9, $result), true);
    }
    public function testGetWorkerClass()
    {
        $countto = new CounttoonehundoSet();
        $result = $countto->getWorkerClass();
        $this->assertSame($result, "YAPF\Junk\Counttoonehundo");
    }
    public function testGetCollectionHash()
    {
        $countto = new CounttoonehundoSet();
        $countto->loadAll();
        $result = $countto->getCollectionHash();
        $expectedhash = "";
        $this->assertSame($result, "af6def892cce35b90a14eaf1a5eea36adda9fa418fdea8e0beef4d1b4d327656");
    }
    public function testGetObjectByField()
    {
        $countto = new CounttoonehundoSet();
        $countto->loadAll();
        $result = $countto->getObjectByField("id", 12);
        $this->assertSame($result->getCvalue(), 2);
        $result = $countto->getObjectByField("id", 712);
        $this->assertSame($result, null);
    }
    public function testGetObjectById()
    {
        $countto = new CounttoonehundoSet();
        $countto->loadAll();
        $result = $countto->getObjectByID(12);
        $this->assertSame($result->getCvalue(), 2);
        $result = $countto->getObjectByID(712);
        $this->assertSame($result, null);
    }
    public function testGetAllByField()
    {
        $countto = new CounttoonehundoSet();
        $countto->loadAll();
        $result = $countto->getAllByField("cvalue");
        $this->assertSame(count($result), 10);
    }
    public function testGetAllIds()
    {
        $countto = new CounttoonehundoSet();
        $countto->loadAll();
        $result = $countto->getAllIds();
        $this->assertSame(count($result), 100);
    }
}