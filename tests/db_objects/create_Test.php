<?php

namespace YAPF\Junk;

use App\Config;
use PHPUnit\Framework\TestCase;
use YAPF\Framework\Config\SimpleConfig;
use YAPF\Junk\Models\Alltypestable;
use YAPF\Junk\Models\Endoftestwithfourentrys;

class DbObjectsCreateTest extends TestCase
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
    public function testCreate()
    {
        global $sql;
        $testing = new Alltypestable();
        $result = $testing->_Stringfield="magic";
        $this->assertSame($result, "magic");
        $result = $testing->_Intfield=44;
        $this->assertSame($result, 44);
        $result = $testing->_Floatfield=2.5;
        $this->assertSame($result, 2.5);
        $result = $testing->createEntry();
        // newID => ?int, rowsAdded => int, status => bool, message => string
        $this->assertSame($result->status, true);
        $this->assertSame($result->message, "ok");
        $this->assertSame($testing->_Id, 2);
    }

    public function testCreateInValid()
    {
        $testing = new Endoftestwithfourentrys();
        $result = $testing->createEntry();
        $this->assertSame($result->message, "Unable to execute because: Column 'value' cannot be null");
        $this->assertSame($result->status, false);
        $this->assertSame($testing->_Id, null);
    }

    public function testCreateThenUpdate()
    {
        $testing = new Endoftestwithfourentrys();
        $result = $testing->_Value="woof";
        $this->assertSame($result, "woof");
        $result = $testing->createEntry();
        // newID => ?int, rowsAdded => int, status => bool, message => string
        $this->assertSame($result->status, true);
        $this->assertSame($result->message, "ok");
        $this->assertSame($testing->_Id, 1);
        $testing->_Value="moo";
        $result = $testing->updateEntry();
        $this->assertSame($result->status, true);
    }
}
