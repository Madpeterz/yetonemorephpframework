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

    /**
     * @depends testAddTables
     */
    public function testCheckLastChanged()
    {
        $cache = $this->getCache();
        $cache->getDriver()->purgeAllKeys();
        $cache = $this->getCache();
        $reply = $cache->addTableToCache("table2" , 10, true, true, false);
        $this->assertSame(true, $reply->status, "vaild setup stopped");
        $reply = $cache->gettablesLastChanged();
        $this->assertSame(true, array_key_exists("table2",$reply), "missing table2 index");
        $this->assertSame(0, $reply["table2"], "expected zero value for last changed due to startup");
        $now = time();
        $cache->markChangeToTable("table2");
        $reply = $cache->gettablesLastChanged();
        $this->assertGreaterThanOrEqual($now, $reply["table2"], "expected change time to have updated");
    }

    /**
     * @depends testCheckLastChanged
     */
    public function testMakeHash()
    {
        $cache = $this->getCache();
        $cache->addTableToCache("demo" , 10, true, true, false);
        $hash = $cache->getHash("demo", 4, true);
        $this->assertSame("63993f21a85dc22e8585",$hash, "hash value changed [did you change this?]");
        $hash = $cache->getHash("demo2", 4, true);
        $this->assertSame(null,$hash, "hash returned when not supported table");
    }

    /**
     * @depends testMakeHash
     */
    public function testCacheWrite()
    {
        $cache = $this->getCache();
        $cache->addTableToCache("demo" , 10, true, true, false);
        $reply = $cache->writeHash("demo", "63993f21a85dc22e8585", ["unittest" => "yes"], true);
        $this->assertSame(true, $reply->status, "write hash failed: ".$reply->message);
        $cache->save();
    }

    /**
     * @depends testCacheWrite
     */
    public function testCacheValid()
    {
        $cache = $this->getCache();
        $cache->addTableToCache("demo" , 10, true, true, false);
        $reply = $cache->cacheValid("demo", "63993f21a85dc22e8585", true);
        $this->assertSame(true, $reply, "Expected a vaild hit for hash+table but failed :(");
    }
}