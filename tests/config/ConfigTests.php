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
        $system->getCacheWorker()->addTableToCache($countto->getTable(),15,true,true);
        $system->startCache();
        $countto = new CounttoonehundoSet();
        $countto->loadAll();
        $system->getCacheWorker()->shutdown();

        $this->assertSame(true,true,"yep");
    }

    public function test_ConfigCacheFlagRedis(): void
    {
        global $system;
        $system = new SimpleConfig();
        $system->configCacheRedisTCP("127.0.0.1");
        $system->setupCache();
        $countto = new CounttoonehundoSet();
        $system->getCacheWorker()->addTableToCache($countto->getTable(),15,true,true);
        $system->startCache();

        $countto = new CounttoonehundoSet();
        $countto->loadAll();
        $system->getCacheWorker()->shutdown();
        $this->assertSame("Predis",$system->getCacheWorker()->getDriver()->driverName(),"Wrong cache driver");
        $stats = $system->getCacheWorker()->getStats();
        $this->assertStringContainsString(1,$stats->reads,"incorrect counters");
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
        $this->assertSame(1,$system->getSQL()->getSQLstats()["selects"],"incorrect number of selects");
    }
}