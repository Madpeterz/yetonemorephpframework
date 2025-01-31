<?php

namespace YAPF\Junk;

use App\Config;
use PHPUnit\Framework\TestCase;
use YAPF\Framework\Config\SimpleConfig;
use YAPF\Framework\DbObjects\GenClass\GenClass as GenClass;
use YAPF\Framework\DbObjects\CollectionSet\CollectionSet as CollectionSet;
use YAPF\Junk\Models\Counttoonehundo;
use YAPF\Junk\Models\Endoftestempty;
use YAPF\Junk\Models\Relationtestinga;
use YAPF\Junk\Sets\CounttoonehundoSet;
use YAPF\Junk\Sets\EndoftestemptySet;
use YAPF\Junk\Sets\LiketestsSet;
use YAPF\Junk\Sets\RelationtestingaSet;
use YAPF\Junk\Sets\Twintables1Set;

// Do not edit this file, rerun gen.php to update!
class BrokenDbObjectMassive extends genClass
{
    protected $use_table = "test.counttoonehundo";
    protected $dataset = [
        "id" => ["type" => "int", "value" => null],
        "lol" => ["type" => "mouse", "value" => null],
    ];
    public int $_Cvalue {
        get => $this->getField(fieldName: "cvalue");
        set {
            $this->updateField(fieldName: "cvalue", value: $value);
        }
    }
}
class BrokenDbObjectMassiveSet extends CollectionSet
{
    public function __construct()
    {
        parent::__construct("YAPF\Junk\BrokenDbObjectMassive");
    }
}

class CollectionSetTest extends TestCase
{
    protected function setUp(): void
    {
        global $system;
        $system = new Config();
    }
    protected function tearDown(): void
    {
        global $system;
        $system->getSQL()->sqlSave(true);
    }

    public function testResetDbFirst()
    {
        global $system;
        $results = $system->getSQL()->rawSQL("tests/testdataset.sql");
        // [status =>  bool, message =>  string]
        $this->assertSame($results->status, true);
        $this->assertSame($results->commandsRun, 57);
    }
    public function testPurgeCollectionSetEmpty()
    {
        $testing = new CounttoonehundoSet();
        $result = $testing->purgeCollection();
        $this->assertSame($result->message, "Collection empty to start with");
        $this->assertSame($result->itemsRemoved, 0);
        $this->assertSame($result->status, true);
    }
    public function testBulkUpdateSet()
    {
        $testing = new CounttoonehundoSet();
        $testing->loadAll();
        $result = $testing->updateFieldInCollection("cvalue", 55);
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->changes, 100);
        $this->assertSame($result->status, true);
    }
    public function testBulkUpdateNoChanges()
    {
        $testing = new CounttoonehundoSet();
        $testing->loadAll();
        $result = $testing->updateMultipleFieldsForCollection(["cvalue"], [55]);
        $this->assertSame($result->message, "No changes made");
        $this->assertSame($result->changes, 0);
        $this->assertSame($result->status, true);
        $result = $testing->updateMultipleFieldsForCollection([], []);
        $this->assertSame($result->message, "No fields being updated!");
        $this->assertSame($result->changes, 0);
        $this->assertSame($result->status, false);
    }
    public function testBulkUpdateInValidField()
    {
        $testing = new CounttoonehundoSet();
        $testing->loadAll();
        $result = $testing->updateFieldInCollection("turndown4what", "shots");
        $this->assertSame($result->message, "Unable to find fieldtype: turndown4what");
        $this->assertSame($result->changes, 0);
        $this->assertSame($result->status, false);
    }
    public function testBulkUpdateUnknownFieldType()
    {
        $testing = new BrokenDbObjectMassiveSet();
        $testing->addToCollected(new BrokenDbObjectMassive());
        $this->assertNotSame($testing->getLastErrorBasic(), "Attempted to add object to collection that does not support _Id","Failed to add object to collection");
        $result = $testing->updateFieldInCollection("cvalue", 421);
        $this->assertSame($result->message, "Unable to find fieldtype: cvalue");
        $this->assertSame($result->changes, 0);
        $this->assertSame($result->status, false);
    }
    public function testForeach()
    {
        $testing = new CounttoonehundoSet();
        $testing->loadLimited(4);
        $seen_items = 0;
        $expected_keys = [1, 2, 3, 4];
        $seen_keys = [];
        foreach ($testing as $key => $value) {
            if ($value->isLoaded() == true) {
                $seen_items++;
                $seen_keys[] = $key;
            }
        }
        $this->assertSame(4, $seen_items, "Foreach with key has failed");
        $this->assertSame(implode(",", $seen_keys), implode(",", $expected_keys), "Keys do not match as expected");
        $seen_items = 0;
        foreach ($testing as $value) {
            if ($value->isLoaded() == true) {
                $seen_items++;
            }
        }
        $this->assertSame(4, $seen_items, "Foreach without key has failed");
        $Counttoonehundo = new Counttoonehundo();
        $Counttoonehundo->_Cvalue = 99;
        $reply = $Counttoonehundo->createEntry();
        $this->assertSame(true, $reply->status, "Failed to crate testing object");
        $testing->addToCollected($Counttoonehundo);
        $seen_items = 0;
        foreach ($testing as $value) {
            if ($value->isLoaded() == true) {
                $seen_items++;
            }
        }
        $this->assertSame(5, $seen_items, "Foreach without key and added entry has failed");
    }
    public function testGetCollection()
    {
        $testing = new CounttoonehundoSet();
        $testing->loadLimited(4);
        $result = $testing->getCollection();
        $this->assertSame(count($result), 4);
        $this->assertSame($result[0]->_Cvalue, 55);
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
        global $system;
        $results = $system->getSQL()->rawSQL("tests/testdataset.sql");
        // [status =>  bool, message =>  string]
        $this->assertSame($results->status, true);
        $this->assertSame($results->commandsRun, 57);
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
        $this->assertSame($result, "YAPF\Junk\Models\Counttoonehundo");
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
        $this->assertSame($result->_Cvalue, 2);
        $result = $countto->getObjectByField("id", 712);
        $this->assertSame($result, null);
    }
    public function testGetObjectById()
    {
        $countto = new CounttoonehundoSet();
        $countto->loadAll();
        $result = $countto->getObjectByID(12);
        $this->assertSame($result->_Cvalue, 2);
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
    public function testgetAllIds()
    {
        $countto = new CounttoonehundoSet();
        $countto->loadAll();
        $result = $countto->getAllIds();
        $this->assertSame(count($result), 100);
    }
    public function testBulkUpdateBoolWithFalse()
    {
        $endoftest = new EndoftestemptySet();
        $endoftest->loadAll();
        $status = $endoftest->updateMultipleFieldsForCollection(["name", "value"], ["bulk", false]);
        $this->assertSame("ok", $status->message);
        $this->assertSame(true, $status->status);
        $this->assertSame(4, $status->changes);
    }
    public function testloadByValuesandGetFieldType()
    {
        $endoftest = new EndoftestemptySet();
        $status = $endoftest->loadFromIds([4]);
        $this->assertSame("ok", $status->message);
        $this->assertSame(true, $status->status);
        $this->assertSame(1, $status->items);

        $Endoftestempty = new Endoftestempty();
        $result = $Endoftestempty->loadID(4);
        $this->assertSame($result->status, true);

        $reply = $Endoftestempty->getFieldType("id", true);
        $this->assertSame("i", $reply);
    }
    public function testloadMatching()
    {
        $countto = new CounttoonehundoSet();
        $result = $countto->loadMatching(["cvalue" => 16]);
        $this->assertSame($result->status, true);
        $this->assertSame($countto->getCount(), 10);
    }
    public function testGetUnique()
    {
        $countto = new CounttoonehundoSet();
        $countto->loadAll();
        $values = $countto->uniqueCvalues();
        $this->assertSame(count($values), 10, "Expected numbers are not correct");
    }
    public function testGetCollectionToMappedArray()
    {
        $likeTests = new LiketestsSet();
        $likeTests->loadAll();
        $results = $likeTests->getCollectionToMappedArray(["id", "name"], true);
        $this->assertSame(4, count($results), "There should be 4 results in the data :/");
        $this->assertSame(2, count($results[1]), "There should be 2 fields in the data :/");
        $this->assertSame(false, array_key_exists("value", $results[1]), "There should not be the value field in the results");
        $this->assertSame(true, array_key_exists("name", $results[1]), "There should be the name field in the results");

        $results = $likeTests->getCollectionToMappedArray(["value"]);
        $this->assertSame(4, count($results), "There should be 4 results in the data :/");
        $this->assertSame(2, count($results[1]), "There should be 2 fields in the data :/");
        $this->assertSame(false, array_key_exists("value", $results[1]), "There should not be the value field in the results");
        $this->assertSame(true, array_key_exists("name", $results[1]), "There should be the name field in the results");
    }

    public function testLoadMatchingWithArray()
    {
        $relationtestinga = new RelationtestingaSet();
        $results = $relationtestinga->loadMatching(
            [
                "id" => [1, 2]
            ]
        );
        $this->assertSame(true, $results->status, "Failed to load with an array");
        $this->assertSame(2, $results->items, "expected 2 items in the collection");
        $this->assertSame('SELECT * FROM test.relationtestinga  WHERE id IN ( ? , ? )', $relationtestinga->getLastSql(), "SQL is fucked");
    }

    public function testGroupCountInDb()
    {
        $CounttoonehundoSet = new CounttoonehundoSet();
        $result = $CounttoonehundoSet->groupCountInDb("cvalue");
        $this->assertSame(true, $result->status, "Expected a true reply, " . $CounttoonehundoSet->getLastSql());
        $this->assertSame(10, count($result->results), "Expected 11 entrys in the result set " . $CounttoonehundoSet->getLastSql());
        foreach ($result->results as $cvalue => $count) {
            $this->assertSame($count, 10, "Expected cvalue: " . $cvalue . " to have count of 10");
        }
    }

    public function testForeachOverSetThenForeachOverSingle()
    {
        $relationtestinga = new RelationtestingaSet();
        $relationtestinga->loadAll();
        $this->assertSame(2, $relationtestinga->getCount());
        $reply = [];
        $loop = 1;
        foreach ($relationtestinga as $entry) {
            $obj = [];
            foreach ($entry as $key => $value) {
                $obj[$key] = $value;
            }
            $reply[$loop] = $obj;
            $loop++;
        }
        $testObj = [
            1 => ["id" => 1, "name" => "group1", "linkid" => 1],
            2 => ["id" => 2, "name" => "group2", "linkid" => 4],
        ];
        foreach ($reply as $key => $entry) {
            $vs = $testObj[$key];
            $this->assertSame($vs["id"], $entry["id"], "id does not match");
            $this->assertSame($vs["name"], $entry["name"], "name does not match");
            $this->assertSame($vs["linkid"], $entry["linkid"], "linkid does not match");
        }
    }
}
