<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\Config\SimpleConfig;
use YAPF\Junk\Models\Counttoonehundo;
use YAPF\Junk\Models\Liketests;
use YAPF\Junk\Models\Relationtestinga;
use YAPF\Junk\Models\Relationtestingb;
use YAPF\Junk\Models\Weirdtable;
use YAPF\Junk\Sets\CounttoonehundoSet;
use YAPF\Junk\Sets\LiketestsSet;
use YAPF\Junk\Sets\RelationtestingaSet;
use YAPF\Junk\Sets\Twintables1Set;
use YAPF\Junk\Sets\WeirdtableSet;

class DbObjectsLoadTest extends TestCase
{
    protected function setUp(): void
    {
        global $system;
        $system = new SimpleConfig();
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
        $this->assertSame($results->commandsRun, 56);
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
        $this->assertSame($load_status->message, "ok");
        $this->assertSame($load_status->status, true);
        $this->assertSame($load_status->entrys, 100);
    }

    public function testLoadRange()
    {
        $countto = new CounttoonehundoSet();
        $load_status = $countto->loadLimited(44, 1, "id", "DESC");
        $this->assertSame($load_status->message, "ok");
        $this->assertSame($load_status->status, true);
        $this->assertSame($load_status->entrys, 44);
        $firstobj = $countto->getFirst();
        $this->assertSame($firstobj->getId(), 56);
        $this->assertSame($firstobj->getCvalue(), 32);
    }

    public function testLoadNewest()
    {
        $countto = new CounttoonehundoSet();
        $load_status = $countto->loadNewest(5);
        $this->assertSame($load_status->status, true);
        $this->assertSame($load_status->entrys, 5);
        $this->assertSame($load_status->message, "ok");
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
        $this->assertSame($load_status->status, true);
        $this->assertSame($load_status->entrys, 0);
    }

    public function testLoadSingleLoadExtendedTests()
    {
        $countto = new Counttoonehundo();
        $result = $countto->loadByField("id", 44);
        $this->assertSame($result, true);
        $countto = new Counttoonehundo();
        $countto->makedisabled();
        $result = $countto->loadByField("id", 44);
        $this->assertSame($result, false);
        $weird = new Weirdtable();
        $result = $weird->loadByField("weirdb", 3);
        $this->assertSame($weird->getId(), null);
        $this->assertSame($result, false);
        $countto = new Counttoonehundo();
        $result = $countto->loadByField("cvalue", 128);
        $this->assertSame($countto->getLastSql(), "SELECT * FROM test.counttoonehundo  WHERE `cvalue` = ?");
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
        $results = $countto->loadFromIds([1,2,3,4,5,6,7,8,9,19]);
        $this->assertSame($results->status, true);
        $this->assertSame($results->entrys, 10);
        $this->assertSame($results->message, "ok");
        $results = $countto->loadFromIds([]);
        $this->assertSame($results->status, false);
        $this->assertSame($results->entrys, 0);
        $this->assertSame($results->message, "No ids sent!");
    }

    public function testLoadSetloadByField()
    {
        $countto = new CounttoonehundoSet();
        $results = $countto->loadByCvalue(32);
        $this->assertSame($results->status, true);
        $this->assertSame($results->entrys, 10);
        $this->assertSame($results->message, "ok");
        $testing = new WeirdtableSet();
        $results = $testing->loadAll();
        $this->assertSame($results->status, true);
        $this->assertSame($results->entrys, 2);
        $this->assertSame($results->message, "ok");
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
        $results = $countto->loadWithConfig($where_config);
        $this->assertSame($results->status, false);
        $this->assertSame($results->entrys, 0);
        $errormsg = "Unable to load data: ";
        $errormsg .= "Where config failed: count error fields <=> values";
        $this->assertSame($results->message, $errormsg);
    }

    public function testLoadWithConfigOptional()
    {
        $countto = new Counttoonehundo();
        $where_config = [
            "fields" => ["id"],
            "values" => [91],
        ];
        $load_status = $countto->loadWithConfig($where_config);
        $this->assertSame($load_status, true);
        $this->assertSame($countto->getId(), 91);

        $EndEmptySet = new CounttoonehundoSet();
        $where_config = [
            "fields" => ["cvalue"],
            "values" => [8],
        ];
        $reply = $EndEmptySet->loadWithConfig($where_config);
        $this->assertSame($reply->status, true);
        $this->assertSame($EndEmptySet->getCount(), 10);
    }

    public function testloadMatching()
    {
        $countto = new Counttoonehundo();
        $result = $countto->loadMatching(["id"=>4,"cvalue"=>8]);
        $this->assertSame($result, true);
        $this->assertSame($countto->getCvalue(), 8);
    }

    public function testCountinDb()
    {
        $testing = new LiketestsSet();
        $reply = $testing->countInDB();
        $expectedSQL = "SELECT COUNT(id) AS sqlCount FROM test.liketests";
        $this->assertSame($expectedSQL,$testing->getLastSql(),"SQL is not what was expected");
        $this->assertSame(4,$reply,"incorrect count reply");
    }

    public function testLimitedMode()
    {
        $testing = new LiketestsSet();
        $testing->limitFields(["name"]);
        $this->assertSame(true,$testing->getUpdatesStatus(),"Set should be marked as update disabled");
        $testing->loadAll();
        $sqlExpected = 'SELECT id, name FROM test.liketests  ORDER BY id ASC';
        $this->assertSame($sqlExpected,$testing->getLastSql(),"SQL is not what was expected");
        $this->assertSame(4,$testing->getCount(),"Incorrect number of entrys loaded");
        $obj = $testing->getObjectByID(1);
        $this->assertSame("redpondblue 1",$obj->getName(),"Value is not set as expected");
        $this->assertSame(null,$obj->getValue(),"Value is not what is expected");
        $reply = $obj->setValue("fail");
        $this->assertSame(false,$reply->status,"Set a value incorrectly");
        $reply = $testing->updateFieldInCollection("value","failme");
        $this->assertSame(false,$reply->status,"bulk set value incorrectly");
        $reply = $obj->createEntry();
        $this->assertSame(false,$reply->status,"created object incorrectly");
        $testing = new Liketests();
        $testing->limitFields(["name"]);
        $this->assertSame(true,$testing->getUpdatesStatus(),"Single should be marked as update disabled");
        $testing->loadID(1);
        $sqlExpected = "SELECT id, name FROM test.liketests  WHERE `id` = ?";
        $this->assertSame($sqlExpected,$testing->getLastSql(),"SQL is not what was expected");
        $this->assertSame("redpondblue 1",$testing->getName(),"Value is not set as expected");
        $this->assertSame(null,$testing->getValue(),"Value is not what is expected");
    }

    public function test_fetchRelated()
    {
        $groupA = new RelationtestingaSet();
        $groupA->loadAll();
        $this->assertSame(2, $groupA->getCount(), "Incorrect number of A loaded");
        $groupB = $groupA->relatedRelationtestingb();
        $this->assertSame(2, $groupB->getCount(), "Incorrect number of B loaded");

        $groupA = new Relationtestinga();
        $groupA->loadID(1);
        $groupB = $groupA->relatedRelationtestingb();
        $this->assertSame(1, $groupB->getCount(), "Incorrect number of B loaded");

        $A = new Relationtestinga();
        $A->loadID(1);
        $B = $A->relatedRelationtestingb();
        $this->assertSame(1, $B->getCount(), "Incorrect number of B loaded");

        $B = new Relationtestingb();
        $B->loadID(1);
        $A = $B->relatedRelationtestinga();
        $this->assertSame(1, $A->getCount(), "Incorrect number of B loaded");
    }

    public function testForeachOverSingle()
    {
        $testing = new Relationtestinga();
        $testing->loadID(2);
        $countExpectedFields = 0;
        foreach($testing as $fieldname => $fieldvalue)
        {
            if($fieldname == "id") {
                $this->assertSame(2, $fieldvalue, "id is incorrect");
                $countExpectedFields++;
            } elseif($fieldname == "name") {
                $this->assertSame("group2", $fieldvalue, "name is incorrect");
                $countExpectedFields++;
            } elseif($fieldname == "linkid") {
                $this->assertSame(4, $fieldvalue, "linkid is incorrect");
                $countExpectedFields++;
            }
        }
        $this->AssertSame(3,$countExpectedFields, "incorrect number of foreach loops");
        $countExpectedFields = 0;
        $loop = 0;
        foreach($testing as $fieldvalue)
        {
            $countExpectedFields++;
        }
        $this->AssertSame(3,$countExpectedFields, "incorrect number of foreach loops");
    }
}
