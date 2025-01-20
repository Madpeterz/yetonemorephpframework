<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Junk\Models\Counttoonehundo;

class Issue3Test extends TestCase
{
    public function testIssue3()
    {
        $countto = new Counttoonehundo();
        $load_status = $countto->loadID(44);
        $this->assertSame(true, $load_status->status);
        $this->assertSame($countto->_Id, 44);
        $this->assertSame($countto->_Cvalue, 8);
        $mapped_array = $countto->objectToMappedArray();
        $this->assertSame(2,count($mapped_array),"Cvalue and id should both be in the array");
        $mapped_array = $countto->objectToMappedArray(["id"]);
        $this->assertSame(1,count($mapped_array),"only Cvlaue should be found in the array");
    }
}
