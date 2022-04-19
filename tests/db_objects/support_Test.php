<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\Config\SimpleConfig;
use YAPF\Framework\DbObjects\GenClass\GenClass as GenClass;
use YAPF\Framework\Responses\DbObjects\UpdateReply;
use YAPF\Junk\Models\Counttoonehundo;
use YAPF\Junk\Models\Liketests;

class BrokenObjectThatSetsWhatever extends genClass
{
    protected $use_table = "test.counttoonehundo";
    protected $fields = ["id","cvalue"];
    protected $dataset = [
        "id" => ["type" => "int", "value" => null],
        "cvalue" => ["type" => "int", "value" => null],
    ];
    /**
    * setCvalue
    */
    public function setCvalue($newValue, string $fieldName = "cvalue"): UpdateReply
    {
        return $this->updateField($fieldName, $newValue);
    }
}
class DbObjectsSupportTest extends TestCase
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
    public function testLastSql()
    {
        $testing = new Liketests();
        $this->assertSame($testing->getLastSql(), "");
        $testing->loadID(1);
        $this->assertSame($testing->getLastSql(), 'SELECT * FROM test.liketests  WHERE `id` = ?');
    }
    public function testLastSQlWithNullGlobalSql()
    {
        global $system;
        $system = new SimpleConfig();
        $testing = new liketests();
        $this->assertSame($testing->getLastSql(), "");
    }
    public function testPassSetupInValidFields()
    {
        $testing = new Counttoonehundo();
        $result = $testing->setup(["fake" => true]);
        $this->assertSame(true,$result); // InValid fields are ignored
    }
    public function testSetTable()
    {
        $testing = new Counttoonehundo();
        $testing->setTable("wrongtable");
        $this->assertSame($testing->getTable(), "wrongtable");
    }
    public function testSetWeirdness()
    {
        $target = new BrokenObjectThatSetsWhatever();
        $result = $target->setCvalue(new BrokenObjectThatSetsWhatever());
        $this->assertSame($result->message, "System error: Attempt to put a object onto field: cvalue");
        $this->assertSame($result->status, false);
        $result = $target->setCvalue([123,1234,12341]);
        $this->assertSame($result->message, "System error: Attempt to put a array onto field: cvalue");
        $this->assertSame($result->status, false);
        $result = $target->setCvalue("woof", "dognoise");
        $this->assertSame($result->message, "Sorry this object does not have the field: dognoise");
        $this->assertSame($result->status, false);
        $result = $target->setCvalue(33, "id");
        $this->assertSame($result->message, "Sorry this object does not allow you to set the id field!");
        $this->assertSame($result->status, false);
        $target->disableAllowSetField();
        $result = $target->setCvalue(1234);
        $this->assertSame($result->message, "update_field is not allowed for this object");
        $this->assertSame($result->status, false);
    }
}
