<?php

namespace YAPF\Junk;

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
        $system = new SimpleConfig();
    }
    protected function tearDown(): void
    {
        global $system;
        $system->getSQL()->sqlSave(true);
        $system = new SimpleConfig();
    }

    public function testUpdateSingle()
    {
        $target = new Endoftestempty();
        $result = $target->loadByField("name", "yes");
        $this->assertSame($result->status, true, "Load by field failed");
        $result = $target->setName("Magic");
        $this->assertSame(true, $result->status, "Set field via helper failed");
        $result = $target->updateEntry();
        $this->assertSame($result->status, true, "Update failed");
        global $system;
        $system->shutdown();
        $this->assertSame(null, $system->getSQL(), "SQL did not go away"); // reset mysql connection
        $this->setUp();
        $target = new Endoftestempty();
        $result = $target->loadID(1);
        $this->assertSame($result->status,true, "Unable to load id 1 from end of test empty");
        $this->assertSame("Magic", $target->getName(), "Incorrect name value");
    }

    public function testUpdateSet()
    {
        $target = new LiketestsSet();
        $results = $target->loadAll();
        $this->assertSame(true, $results->status, "Incorrect load status");
        $this->assertSame($results->entrys, 4);
        $results = $target->updateFieldInCollection("value", "Song");
        $this->assertSame(true, $results->status, "Incorrect update status");
        $this->assertSame($results->changes, 3);
    }

    public function testUpdateSetInvaild()
    {
        $target = new LiketestsSet();
        $results = $target->loadAll();
        $this->assertSame(true, $results->status, "Incorrect load status");
        $this->assertSame($results->entrys, 4);
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
        $target->setFloatfield(23.4);
        $target->setIntfield(55);
        $testing = $target->setStringfield("Hello world");
        $this->assertSame("value set", $testing->message, "Incorrect message");
        $this->assertSame(1, $testing->changes, "Incorrect number of changes");
        $this->assertSame(true, $testing->status, "Incorrect status flag");
        $createReply = $target->createEntry();
        $this->assertSame("ok", $createReply->message, "Incorrect create entry message");
        $this->assertSame(true, $createReply->status, "Incorrect create entry status");
        $this->assertSame(1, $target->getId(), "Incorrect target id");
        $target->setFloatfield(55.81);
        $results = $target->updateEntry();
        $this->assertSame("ok", $results->message, "Incorrect change message");
        $this->assertSame(1, $results->changes, "Incorrect number of changes");
        $this->assertSame(true, $results->status, "Incorrect update status");
    }
}
