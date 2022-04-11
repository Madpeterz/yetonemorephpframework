<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Junk\Models\Counttoonehundo;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;


$sql = null;
class Issue5Test extends TestCase
{
    /* @var YAPF\Framework\MySQLi\MysqliEnabled $sql */
    protected function setUp(): void
    {
    }
    protected function tearDown(): void
    {
        global $system;
        $system->getSQL()->sqlSave(true);
    }

    public function testIssue5()
    {
        global $sql;
        $countto = new Counttoonehundo();
        $load_status = $countto->loadID(44);
        $this->assertSame($load_status, true);
        $this->assertSame($countto->getId(), 44);
        $this->assertSame($countto->getCvalue(), 55);
        $status = $countto->defaultValues();
        $this->assertSame(true, $status, "defaultValues failed: ".$countto->getLastErrorBasic());
        $save = $countto->updateEntry();
        $this->assertSame("ok", $save->message, "failed to make changes");
        $this->assertSame(true, $save->status, "failed to make changes");
        $countto = new Counttoonehundo();
        $load_status = $countto->loadID(44);
        $this->assertSame($countto->getCvalue(), 1, "value not changed via defaultValues");
    }
}
