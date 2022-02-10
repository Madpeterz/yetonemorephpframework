<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Cache\Cache;
use YAPF\Cache\Drivers\Redis;
use YAPF\Config\SimpleConfig;
use YAPF\Junk\Models\Counttoonehundo;
use YAPF\Junk\Models\Liketests;
use YAPF\Junk\Sets\CounttoonehundoSet;
use YAPF\Junk\Sets\LiketestsSet;

class ConfigTests extends TestCase
{
    public function test_yep(): void
    {
        $this->assertSame(true,true,"...");
    }
}