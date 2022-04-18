<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\Config\SimpleConfig;
use YAPF\Junk\Models\Alltypestable;
use YAPF\Junk\Models\Counttoonehundo;
use YAPF\Junk\Models\Relationtestingb;
use YAPF\Junk\Sets\CounttoonehundoSet;

class DbObjectsRemoveTest extends TestCase
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

    public function testRemoveSingle()
    {
        $target = new Alltypestable();
        $result = $target->loadID(1);
        $this->assertSame(true,$result->status);
        $result = $target->removeEntry();
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->status, true);
    }
    public function testRemoveSingleInValid()
    {
        $target = new Counttoonehundo();
        $result = $target->removeEntry();
        $this->assertSame($result->message, "this object is not loaded!");
        $this->assertSame($result->status, false);
    }
    public function testRemoveSet()
    {
        $target = new CounttoonehundoSet();
        $result = $target->loadAll();
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->status, true);
        $this->assertSame($target->getCount(), 100);
        $result = $target->purgeCollection();
        $this->assertSame($result->message, "ok");
        $this->assertSame($result->status, true);
        $this->assertSame($result->itemsRemoved, 100);
    }
    public function testRemoveSetRejectRelationship()
    {
        $target = new Relationtestingb();
        $result = $target->loadID(1);
        $this->assertSame(true,$result->status);
        $result = $target->removeEntry();
        $reject_message = 'Unable to execute because: Cannot delete or update a parent row: ';
        $reject_message .= 'a foreign key constraint fails (`test`.`relationtestinga`, CONSTRAINT `testingb_in_use` ';
        $reject_message .= 'FOREIGN KEY (`linkid`) REFERENCES `relationtestingb` (`id`))';
        $message = strtr($result->message, [" ON UPDATE NO ACTION" => ""]);
        $this->assertSame($message, $reject_message);
        $this->assertSame($result->status, false);
    }
}
