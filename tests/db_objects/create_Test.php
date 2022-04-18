<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\Config\SimpleConfig;
use YAPF\Junk\Models\Alltypestable;
use YAPF\Junk\Models\Endoftestwithfourentrys;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;

class DbObjectsCreateTest extends TestCase
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
    public function testCreate()
    {
        global $sql;
        $testing = new Alltypestable();
        $result = $testing->setStringfield("magic");
        $this->assertSame($result->status, true);
        $result = $testing->setIntfield(44);
        $this->assertSame($result->status, true);
        $result = $testing->setFloatfield(2.5);
        $this->assertSame($result->status, true);
        $result = $testing->createEntry();
        // newID => ?int, rowsAdded => int, status => bool, message => string
        $this->assertSame($result->status, true);
        $this->assertSame($result->message, "ok");
        $this->assertSame($testing->getId(), 2);
    }

    public function testCreateInvaild()
    {
        $testing = new Endoftestwithfourentrys();
        $result = $testing->createEntry();
        $this->assertSame($result->message, "Unable to execute because: Column 'value' cannot be null");
        $this->assertSame($result->status, false);
        $this->assertSame($testing->getId(), null);
    }

    public function testCreateThenUpdate()
    {
        $testing = new Endoftestwithfourentrys();
        $result = $testing->setValue("woof");
        $this->assertSame($result->status, true);
        $result = $testing->createEntry();
        // newID => ?int, rowsAdded => int, status => bool, message => string
        $this->assertSame($result->status, true);
        $this->assertSame($result->message, "ok");
        $this->assertSame($testing->getId(), 1);
        $testing->setValue("moo");
        $result = $testing->updateEntry();
        $this->assertSame($result->status, true);
    }
}
