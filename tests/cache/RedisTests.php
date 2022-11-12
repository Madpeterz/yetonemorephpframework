<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\Cache\Drivers\Redis;

class RedisTests extends TestCase
{
    protected function getCache(): Redis
    {
        $cache = new Redis();
        $cache->connectTCP();
        return $cache;
    }

    public function testStart(): void
    {
        $result = $this->getCache()->start();
        $this->assertSame(true, $result->status, "failed to start cache");
    }

    /**
     * @depends testStart
     */
    public function testWriteEntry(): void
    {
        $cache = $this->getCache();
        $cache->start();
        $result = $cache->writeKey("hello", "world", time()+120);
        $this->assertSame($result->status, true, "failed to write to cache: ".$result->message);
    }

    /**
     * @depends testPurgeAll
     */
    public function testReadEntry(): void
    {
        $cache = $this->getCache();
        $cache->start();
        $cache->writeKey("magic", "missile", time()+120);
        $result = $cache->readKey("magic");
        $this->assertSame("missile", $result->value, "failed to read from cache: ".$result->message);
        $cache->stop();
    }

    /**
     * @depends testWriteEntry
     */
    public function testDeleteEntry(): void
    {
        $cache = $this->getCache();
        $cache->start();
        $cache->writeKey("pop", "corn", time()+120);
        $cache->stop();
        $cache->start();
        $result = $cache->deleteKey("pop");
        $this->assertSame(true, $result->status, "Failed to delete key: ".$result->message);
        $cache->deleteKey("hello");
    }

    /**
     * @depends testStart
     */
    public function testReadNull(): void
    {
        $cache = $this->getCache();
        $cache->start();
        $result = $cache->readKey("invaildkey");
        $this->assertSame(false, $result->status, "Expected false status");
        $this->assertSame(null, $result->value, "Expected null but got something");
        $this->assertSame("null result", $result->message,  "Message changed, please check and update the tests");
    }

    /**
     * @depends testDeleteEntry
     */
    public function testPurgeAll(): void
    {
        $cache = $this->getCache();
        $cache->start();
        $cache->purgeAllKeys();
        $loop = 0;
        while($loop < 100)
        {
            $cache->writeKey("r".$loop, "v".$loop, time()+120);
            $loop++;
        }
        $result = $cache->purgeAllKeys();
        $this->assertSame(true, $result->status, "Failed to purge all keys: ".$result->message);
        $this->assertSame(100, $result->keysDeleted, "incorrect number of keys deleted [you should not be testing on a live system :(]");
    }

    /**
     * @depends testPurgeAll
     */
    public function testDeleteKeysBulk(): void
    {
        $cache = $this->getCache();
        $cache->start();
        $loop = 0;
        $klist = [];
        $rando = rand(5,10);
        while($loop < (10+$rando))
        {
            $klist[] = "kb".$loop;
            $cache->writeKey("kb".$loop, "v".$loop, time()+120);
            $loop++;
        }
        $result = $cache->deleteKeys($klist);
        $this->assertSame(true, $result->status, "Failed to delete selected keys: ".$result->message);
        $this->assertSame(count($klist), $result->keysDeleted, "incorrect number of keys deleted [you should not be testing on a live system :(]");
    }


    /**
     * @depends testPurgeAll
     */
    public function testWriteInvaildTime(): void
    {
        $cache = $this->getCache();
        $cache->start();
        $result = $cache->writeKey("badtime", "buddy", time()-120);
        $this->assertSame(false, $result->status, "Cache write with invaild time accepted!");
        $this->assertSame("Invaild expire unixtime", $result->message, "Message changed, please check and update the tests");
        
    }

    /**
     * @depends testPurgeAll
     */
    public function testLongKeyName(): void
    {
        $longkeyname = "asdasdasd";
        while(strlen($longkeyname) < 1500) {
            $longkeyname .= $longkeyname;
        }
        $cache = $this->getCache();
        $cache->start();
        $result = $cache->writeKey($longkeyname, "buddy", time()+120);
        $this->assertSame(false, $result->status, "Accepted writeKey with long key name :(");
        $this->assertSame("Invaild key length [max 1000]", $result->message, "Message changed, please check and update the tests");
        $result = $cache->readKey($longkeyname);
        $this->assertSame(false, $result->status, "Accepted writeKey with long key name :(");
        $this->assertSame("Invaild key length [max 1000]", $result->message, "Message changed, please check and update the tests");
    }

    /**
     * @depends testReadEntry
     */
    public function testRecoverRead(): void
    {
        $cache = $this->getCache();
        $cache->start();
        $result = $cache->readKey("magic");
        $this->assertSame("missile", $result->value, "failed to read from cache: ".$result->message);
        $cache->deleteKey("magic");
    }


}
