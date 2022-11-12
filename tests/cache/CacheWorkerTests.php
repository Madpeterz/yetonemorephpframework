<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\Cache\CacheWorker;
use YAPF\Framework\Cache\Drivers\Redis;

class AccCacheWorker extends CacheWorker
{
    public function gettablesLastChanged(): array
    {
        return $this->tablesLastChanged;
    }
}

class CacheWorkerTests extends TestCase
{
    protected function getCache(): AccCacheWorker
    {
        $cache = new AccCacheWorker(new Redis());
        $cache->getDriver()->connectTCP();
        return $cache;
    }

    public function testAddTables(): void
    {
        $cache = $this->getCache();
        $reply = $cache->addTableToCache("table1" , -10, true, true, false);
        $this->assertSame(false, $reply->status, "invaild max age not stopped");
        $reply = $cache->addTableToCache("table1" , 10, true, true, false);
        $this->assertSame(true, $reply->status, "vaild setup stopped");
    }

    public function testCheckLastChanged()
    {
        $cache = $this->getCache();
        $reply = $cache->addTableToCache("table2" , 10, true, true, false);
        $this->assertSame(true, $reply->status, "vaild setup stopped");
        $reply = $cache->gettablesLastChanged();
        $this->assertSame(true, array_key_exists("table2",$reply), "missing table2 index");
    }
}