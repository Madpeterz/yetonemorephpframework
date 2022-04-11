<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Junk\Models\Counttoonehundo;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;


$sql = null;
class Issue4Test extends TestCase
{
    /* @var YAPF\Framework\MySQLi\MysqliEnabled $sql */
    protected $sql = null;
    protected function setUp(): void
    {
        global $sql;
        $sql = new MysqliConnector();
    }
    protected function tearDown(): void
    {
        global $sql;
        $sql->sqlSave(true);
        $sql = null;
    }

    public function testIssue4()
    {
        global $sql;
        $countto = new Counttoonehundo();
        $load_status = $countto->loadID(44);
        $this->assertSame($load_status, true);
        $this->assertSame($countto->getId(), 44);
        $this->assertSame($countto->getCvalue(), 10);
        $reply = $countto->bulkChange(["cvalue" => 55]);
        $this->assertSame("ok", $reply->message, "Wrong error message");
        $this->assertSame(true, $reply->status, "bulk change failed");
        $save = $countto->updateEntry();
        $this->assertSame("ok", $save->message, "failed to make changes");
        $this->assertSame(true, $save->status, "failed to make changes");
        $countto = new Counttoonehundo();
        $load_status = $countto->loadID(44);
        $this->assertSame($countto->getCvalue(), 55, "value not changed via bulkChange");
    }
}
