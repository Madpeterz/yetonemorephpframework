<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\Config\SimpleConfig;
use YAPF\Junk\Sets\CounttoonehundoSet;

class ConfigTests extends TestCase
{
    public function test_forcePushToCache(): void
    {
        global $system;
        $system = new SimpleConfig();
        $system->configCacheRedisTCP("127.0.0.1");
        $system->setupCache();
        $countto = new CounttoonehundoSet();
        $system->getCacheDriver()->addTableToCache($countto->getTable(),15,true,true);
        $system->startCache();
        $countto = new CounttoonehundoSet();
        $countto->loadAll();
        $system->getCacheDriver()->shutdown();

        $this->assertSame(true,true,"yep");
    }

    public function test_ConfigCacheFlagRedis(): void
    {
        global $system;
        $system = new SimpleConfig();
        $system->configCacheRedisTCP("127.0.0.1");
        $system->setupCache();
        $countto = new CounttoonehundoSet();
        $system->getCacheDriver()->addTableToCache($countto->getTable(),15,true,true);
        $system->startCache();

        $countto = new CounttoonehundoSet();
        $countto->loadAll();
        $system->getCacheDriver()->shutdown();
        $this->assertSame("Redis",$system->getCacheDriver()->getDriverName(),"Wrong cache driver");
        $this->assertStringContainsString('"reads":1,',json_encode($system->getCacheDriver()->getStatusCounters()),"incorrect counters");
        $this->assertSame(true,$system->getCacheDriver()->getStatusConnected(),"Redis did not connect");
    }

    public function test_ConfigCacheFlagNoCache(): void
    {
        global $system;
        $system = new SimpleConfig();
        $countto = new CounttoonehundoSet();
        $system->configCacheDisabled();
        $system->setupCache();
        $system->startCache();
        $countto->loadAll();
        $this->assertSame(null,$system->getCacheDriver(),"Wrong cache driver");
        $this->assertSame(1,$system->getSQL()->getSQLstats()["selects"],"incorrect number of selects");
    }
}