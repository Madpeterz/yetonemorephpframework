<?php

namespace YAPF\Junk;

use App\Config;
use Exception;
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
    public int $_Cvalue {
        get => $this->getField(fieldName: "cvalue");
        set {
            $this->updateField(fieldName: "cvalue", value: $value);
        }
    }
}
class DbObjectsSupportTest extends TestCase
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
        $system = new Config();
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
        $target->disableAllowSetField();
        $target->_Cvalue = 1234;
        $this->assertSame($target->getLastErrorBasic(), "update_field is not allowed for this object");
    }
}
