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
        $this->assertSame(true, $result, "Load by field failed");
        $result = $target->setName("Magic");
        $this->assertSame(true, $result["status"], "Set field via helper failed");
        $result = $target->updateEntry();
        $this->assertSame($result["status"], true, "Update failed");
        global $system;
        $system->shutdown();
        $this->assertSame(null, $system->getSQL(), "SQL did not go away"); // reset mysql connection
        $this->setUp();
        $target = new Endoftestempty();
        $result = $target->loadID(1);
        $this->assertSame(true, $result, "Unable to load id 1 from end of test empty");
        $this->assertSame("Magic", $target->getName(), "Incorrect name value");
    }

    public function testUpdateSet()
    {
        $target = new LiketestsSet();
        $result = $target->loadAll();
        $this->assertSame(true, $result["status"], "Incorrect load status");
        $this->assertSame($result["count"], 4);
        $result = $target->updateFieldInCollection("value", "Song");
        $this->assertSame(true, $result["status"], "Incorrect update status");
        $this->assertSame($result["changes"], 3);
    }

    public function testUpdateSetInvaild()
    {
        $target = new LiketestsSet();
        $result = $target->loadAll();
        $this->assertSame(true, $result["status"], "Incorrect load status");
        $this->assertSame($result["count"], 4);
        $result = $target->updateFieldInCollection("value", null);
        $this->assertSame(false, $result["status"], "Incorrect update status");
        $this->assertSame($result["changes"], 0);
        $fail_message = "Update failed because:unable to execute because: Column 'value' cannot be null";
        $this->assertSame($result["message"], $fail_message);
    }

    public function testUpdateSetEmpty()
    {
        $target = new LiketestsSet();
        $result = $target->updateFieldInCollection("value", "yes");
        $this->assertSame(false, $result["status"], "Incorrect update status");
        $this->assertSame($result["changes"], 0);
        $fail_message = "Nothing loaded in collection";
        $this->assertSame($result["message"], $fail_message);
    }

    public function testUpdateFloat()
    {
        $target = new Alltypestable();
        $target->setFloatfield(23.4);
        $target->setIntfield(55);
        $target->setStringfield("Hello world");
        $target->createEntry();
        $target->setFloatfield(55.81);
        $result = $target->updateEntry();
        $this->assertSame(true, $result["status"], "Incorrect update status");
        $this->assertSame($result["changes"], 1);
    }
}
