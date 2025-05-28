<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Junk\test\Counttoonehundo;

class Issue5Test extends TestCase
{
    public function testIssue5()
    {
        $countto = new Counttoonehundo();
        $load_status = $countto->loadID(44);
        $this->assertSame(true, $load_status->status);
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
