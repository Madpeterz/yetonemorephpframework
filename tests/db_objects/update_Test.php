<?php

namespace YAPF\Junk;

use App\Config;
use PHPUnit\Framework\TestCase;
use YAPF\Framework\Config\SimpleConfig;
use YAPF\Junk\Models\Alltypestable;
use YAPF\Junk\Models\Endoftestempty;
use YAPF\Junk\Sets\LiketestsSet;

class DbObjectsUpdateTest extends TestCase
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
        $system = new Config();
    }

    public function testUpdateSingle()
    {
        $target = new Endoftestempty();
        $result = $target->loadByField("name", "yes");
        $this->assertSame($result->status, true, "Load by field failed");
        $target->_Name = "Magic";
        $result = $target->updateEntry();
        $this->assertSame($result->status, true, "Update failed: " . $result->message);
        global $system;
        $system->shutdown();
        $this->assertSame(null, $system->getSQL(), "SQL did not go away"); // reset mysql connection
        $this->setUp();
        $target = new Endoftestempty();
        $result = $target->loadID(1);
        $this->assertSame($result->status, true, "Unable to load id 1 from end of test empty");
        $this->assertSame("Magic", $target->_Name, "Incorrect name value");
    }

    /**
     * @depends testUpdateSingle
     */
    public function testGetPendingChanges()
    {
        $target = new Endoftestempty();
        $result = $target->loadByField("name", "Magic");
        $this->assertSame($result->status, true, "Load by field failed");
        $target->_Name = "ChangeMagic";
        $result = $target->getPendingChanges();
        $this->assertSame(true, $result->vaild, "The change is not vaild");
        $this->assertSame(1, $result->changes, "incorrect number of changes made: " . json_encode($result->fieldsChanged));
        $this->assertSame(true, in_array("name", $result->fieldsChanged), "Expected field is not in the change list");
        $this->assertSame("name", $result->fieldsChanged[0], "Incorrect field changed");
        $this->assertsame("Magic", $result->oldValues["name"], "Incorrect old field value");
        $this->assertsame("ChangeMagic", $result->newValues["name"], "Incorrect new field value");
    }

    public function testUpdateSet()
    {
        $target = new LiketestsSet();
        $results = $target->loadAll();
        $this->assertSame(true, $results->status, "Incorrect load status");
        $this->assertSame($results->items, 4);
        $results = $target->updateFieldInCollection("value", "Song");
        $this->assertSame(true, $results->status, "Incorrect update status");
        $this->assertSame($results->changes, 3);
    }

    public function testUpdateSetInValid()
    {
        $target = new LiketestsSet();
        $results = $target->loadAll();
        $this->assertSame(true, $results->status, "Incorrect load status");
        $this->assertSame($results->items, 4);
        $results = $target->updateFieldInCollection("value", null);
        $this->assertSame(false, $results->status, "Incorrect update status");
        $this->assertSame($results->changes, 0);
        $fail_message = "Update failed because:Unable to execute because: Column 'value' cannot be null";
        $this->assertSame($results->message, $fail_message);
    }

    public function testUpdateSetEmpty()
    {
        $target = new LiketestsSet();
        $results = $target->updateFieldInCollection("value", "yes");
        $this->assertSame(false, $results->status, "Incorrect update status");
        $this->assertSame($results->changes, 0);
        $fail_message = "Nothing loaded in collection";
        $this->assertSame($results->message, $fail_message);
    }

    public function testUpdateFloat()
    {
        $target = new Alltypestable();
        $target->_Floatfield = 23.4;
        $target->_Intfield = 55;
        $target->_Stringfield = "Hello world";
        $createReply = $target->createEntry();
        $this->assertSame("ok", $createReply->message, "Incorrect create entry message");
        $this->assertSame(true, $createReply->status, "Incorrect create entry status");
        $this->assertSame(1, $target->_Id, "Incorrect target id");
        $target->_Floatfield = 55.81;
        $results = $target->updateEntry();
        $this->assertSame("ok", $results->message, "Incorrect change message");
        $this->assertSame(1, $results->changes, "Incorrect number of changes");
        $this->assertSame(true, $results->status, "Incorrect update status");
    }
}
