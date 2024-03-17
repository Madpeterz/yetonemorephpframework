<?php

namespace YAPF\Junk;

use App\Config;
use PHPUnit\Framework\TestCase;
use YAPF\Framework\Cache\CacheWorker;
use YAPF\Framework\Cache\Drivers\Redis;
use YAPF\Junk\Models\Encryptedcheck;
use YAPF\Junk\Sets\LiketestsSet;

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
        $this->assertSame(true, $result->status, "failed to start cache: " . $result->message);
    }

    /**
     * @depends testStart
     */
    public function testWriteEntry(): void
    {
        $this->getCache()->purgeAllKeys();
        $cache = $this->getCache();
        $cache->start();
        $result = $cache->writeKey("hello", "world", time() + 120);
        $this->assertSame($result->status, true, "failed to write to cache: " . $result->message);
    }

    /**
     * @depends testPurgeAll
     */
    public function testReadEntry(): void
    {
        $cache = $this->getCache();
        $cache->start();
        $cache->writeKey("magic", "missile", time() + 120);
        $result = $cache->readKey("magic");
        $this->assertSame("missile", $result->value, "failed to read from cache: " . $result->message);
        $cache->stop();
    }

    /**
     * @depends testWriteEntry
     */
    public function testDeleteEntry(): void
    {
        $cache = $this->getCache();
        $cache->start();
        $cache->writeKey("pop", "corn", time() + 120);
        $cache->stop();
        $cache->start();
        $result = $cache->deleteKey("pop");
        $this->assertSame(true, $result->status, "Failed to delete key: " . $result->message);
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
        while ($loop < 100) {
            $cache->writeKey("r" . $loop, "v" . $loop, time() + 120);
            $loop++;
        }
        $result = $cache->purgeAllKeys();
        $this->assertSame(true, $result->status, "Failed to purge all keys: " . $result->message);
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
        $rando = rand(5, 10);
        while ($loop < (10 + $rando)) {
            $klist[] = "kb" . $loop;
            $cache->writeKey("kb" . $loop, "v" . $loop, time() + 120);
            $loop++;
        }
        $result = $cache->deleteKeys($klist);
        $this->assertSame(true, $result->status, "Failed to delete selected keys: " . $result->message);
        $this->assertSame(count($klist), $result->keysDeleted, "incorrect number of keys deleted [you should not be testing on a live system :(]");
    }


    /**
     * @depends testPurgeAll
     */
    public function testWriteInvaildTime(): void
    {
        $cache = $this->getCache();
        $cache->start();
        $result = $cache->writeKey("badtime", "buddy", time() - 120);
        $this->assertSame(false, $result->status, "Cache write with invaild time accepted!");
        $this->assertSame("Invaild expire unixtime", $result->message, "Message changed, please check and update the tests");
    }

    /**
     * @depends testPurgeAll
     */
    public function testLongKeyName(): void
    {
        $longkeyname = "asdasdasd";
        while (strlen($longkeyname) < 1500) {
            $longkeyname .= $longkeyname;
        }
        $cache = $this->getCache();
        $cache->start();
        $result = $cache->writeKey($longkeyname, "buddy", time() + 120);
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
        $this->assertSame("missile", $result->value, "failed to read from cache: " . $result->message);
        $cache->deleteKey("magic");
    }

    /**
     * @depends testRecoverRead
     */
    public function testcountInDBRedis(): void
    {
        global $system;
        $system = new Config();
        $system->configCacheRedisTCP("localhost");
        $system->setupCache();
        $system->getCacheWorker()->addTableToCache("test.liketests", 15, true, true, false);
        $system->getCacheWorker()->purge();
        $testing = new LiketestsSet();
        $reply = $testing->countInDB();
        $expectedSQL = "SELECT COUNT(id) AS sqlCount FROM test.liketests";
        $this->assertSame($expectedSQL, $testing->getLastSql(), "SQL is not what was expected");
        $this->assertSame(true, $reply->status, "count in db failed in some way: " . $reply->message);
        $this->assertSame(4, $reply->items, "incorrect count reply");
        $this->assertSame(1, $system->getCacheWorker()->getStats()->writes, "expected to write 1 thing");

        $testing = new LiketestsSet();
        $testing->enableConsoleErrors();
        $reply = $testing->countInDB();
        $expectedSQL = "SELECT COUNT(id) AS sqlCount FROM test.liketests";
        $this->assertSame($expectedSQL, $testing->getLastSql(), "SQL is not what was expected");
        $this->assertSame(true, $reply->status, "count in db failed in some way: " . $reply->message);
        $this->assertSame(4, $reply->items, "incorrect count reply");
        $this->assertSame(1, $system->getCacheWorker()->getStats()->reads, "expected to read 1 thing");
    }
    public function testEncodedRedis(): void
    {
        global $system;
        $system = new Config();
        $results = $system->getSQL()->rawSQL("tests/testdataset.sql");
        // [status =>  bool, message =>  string]
        $this->assertSame($results->status, true);
        $this->assertSame($results->commandsRun, 57);
        $system->configCacheRedisTCP("localhost");
        $system->setupCache();
        $reply = $system->getCacheWorker()->setEncryptKeyCode("example");
        $this->assertSame(true, $reply->status, "unable to setEncryptKeyCode because: " . $reply->message);
        $reply = $system->getCacheWorker()->addTableToCache("test.encryptedcheck", 15, true, true, true);
        $this->assertSame(true, $reply->status, "unable to add table to cache because: " . $reply->message);
        $system->getCacheWorker()->purge();
        $testme = new Encryptedcheck();
        $testme->setName("example");
        $testme->setValue("encoded");
        $reply = $testme->createEntry();
        $this->assertSame(true, $reply->status, "Failed to create entry: " . $reply->message);
        $this->assertSame(1, $reply->newId, "Expected id to be 1");
        $this->assertSame(0, $system->getCacheWorker()->getStats()->miss, "Should not have a miss as no load");
        $this->assertSame(0, $system->getCacheWorker()->getStats()->writes, "Should not have a write as no read");
        $system->shutdown();
        // should now be in DB raw and not in cache
        $system = new Config();
        $system->configCacheRedisTCP("localhost");
        $system->setupCache();
        $system->getCacheWorker()->setEncryptKeyCode("example");
        $system->getCacheWorker()->addTableToCache("test.encryptedcheck", 15, true, true, true);
        $loadtestme = new Encryptedcheck();
        $reply = $loadtestme->loadId(1);
        $this->assertSame(true, $reply->status, "Failed to load from DB: " . $reply->message);
        $this->assertSame(1, $loadtestme->getId(), "Expected to have loaded id 1");
        // should now also be in cache but encoded
        $this->assertSame(1, $system->getCacheWorker()->getStats()->miss, "Attempted read but found incorrectly");
        $this->assertSame(1, $system->getCacheWorker()->getStats()->writes, "Should have added the missed entry into cache");
        $system->shutdown();
        $system = new Config();
        $system->configCacheRedisTCP("localhost");
        $system->setupCache();
        $system->getCacheWorker()->setEncryptKeyCode("example");
        $system->getCacheWorker()->addTableToCache("test.encryptedcheck", 15, true, true, true);
        $whereConfig = [
            "fields" => ["id"],
            "matches" => ["="],
            "values" => [1],
            "types" => ["i"],
        ];
        $basic_config = ["table" => "test.encryptedcheck"];
        $hash = $system->getCacheWorker()->getHash(
            "test.encryptedcheck",
            3,
            true,
            $whereConfig,
            ["single" => true],
            ["single" => true],
            $basic_config
        );
        $this->assertSame("1b5866a666e9b2340396", $hash, "Created hash does not match what is expected");
        $directRead = $this->startRedisCache();
        $directRead->getDriver()->start();
        $keyslist = $directRead->getDriver()->listKeys();
        $this->assertSame(true, in_array($hash, $keyslist->keys), "Expected key list is not correct: " . json_encode($keyslist));
        $reply = $directRead->getDriver()->readKey($hash);
        $this->assertSame(true, $reply->status, "Failed to read key: " . $reply->message);
        $expected = 'jMeAC6ET7XR';
        $data = json_decode($reply->value, true);
        $this->assertStringContainsString($expected, $data["data"], "Data from read is not as expected");
    }


    protected function startRedisCache(): CacheWorker
    {
        return new CacheWorker($this->connectRedisToSource());
    }

    protected function connectRedisToSource(): Redis
    {
        $driver = new Redis();
        $driver->setTimeout(10);
        $driver->connectTCP();
        return $driver;
    }
}
