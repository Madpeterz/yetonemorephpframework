<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Junk\Models\Counttoonehundo;

class Issue4Test extends TestCase
{
    public function testIssue4()
    {
        $countto = new Counttoonehundo();
        $load_status = $countto->loadID(44);
        $this->assertSame(true, $load_status->status);
        $this->assertSame($countto->getId(), 44);
        $this->assertSame($countto->getCvalue(), 8);
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
