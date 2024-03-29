<?php

namespace YAPF\Junk;

use App\Config;
use PHPUnit\Framework\TestCase;
use YAPF\Framework\Cache\CacheWorker;
use YAPF\Framework\Cache\Drivers\Redis;
use YAPF\Framework\Config\SimpleConfig;
use YAPF\Junk\Models\Counttoonehundo;

class AccCacheWorker extends CacheWorker
{
    public function gettablesLastChanged(): ?array
    {
        return $this->tablesLastChanged;
    }

    public function forceAdjustTablesLastChanged(string $table, string $index, int $newValue)
    {
        $this->tablesLastChanged[$table][$index] = $newValue;
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
        $reply = $cache->addTableToCache("table1", -10, true, true, false);
        $this->assertSame(false, $reply->status, "invaild max age not stopped");
        $reply = $cache->addTableToCache("table1", 10, true, true, false);
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
        $reply = $cache->addTableToCache("table2", 10, true, true, false);
        $this->assertSame(true, $reply->status, "vaild setup stopped");
        $reply = $cache->gettablesLastChanged();
        $this->assertSame(true, array_key_exists("table2", $reply), "missing table2 index");
        $this->assertSame(0, $reply["table2"]["time"], "expected zero value for last changed due to startup");
        $this->assertSame(1, $reply["table2"]["version"], "expected zero value for last changed due to startup");
        $now = time();
        $cache->markChangeToTable("table2");
        $reply = $cache->gettablesLastChanged();
        $this->assertGreaterThanOrEqual($now, $reply["table2"]["time"], "expected change time to have updated");
        $this->assertSame(2, $reply["table2"]["version"], "expected change to version");
    }

    /**
     * @depends testCheckLastChanged
     */
    public function testMakeHash()
    {
        $cache = $this->getCache();
        $cache->addTableToCache("demo", 10, true, true, false);
        $hash = $cache->getHash("demo", 4, true);
        $this->assertSame("63993f21a85dc22e8585", $hash, "hash value changed [did you change this?]");
        $hash = $cache->getHash("demo2", 4, true);
        $this->assertSame(null, $hash, "hash returned when not supported table");
    }

    /**
     * @depends testMakeHash
     */
    public function testCacheWrite()
    {
        $cache = $this->getCache();
        $cache->addTableToCache("demo", 10, true, true, false);
        $reply = $cache->writeHash("demo", "63993f21a85dc22e8585", ["unittest" => "yes"], true);
        $this->assertSame(true, $reply->status, "write hash failed: " . $reply->message);
        $cache->save();
    }

    /**
     * @depends testCacheWrite
     */
    public function testReadHash()
    {
        $cache = $this->getCache();
        $cache->addTableToCache("demo", 10, true, true, false);
        $reply = $cache->readHash("demo", "63993f21a85dc22e8585", true);
        $this->assertSame(true, is_array($reply), "Failed to read hash as expected");
        $this->assertSame(true, array_key_exists("unittest", $reply["data"]), "missing unittest key from recovered data");
        $this->assertSame("yes", $reply["data"]["unittest"], "incorrect value from recovered data");
    }

    /**
     * @depends testReadHash
     */
    public function testReadHashInvaildatedDueToChange()
    {
        $cache = $this->getCache();
        $cache->addTableToCache("demo", 10, true, true, false);
        $cache->markChangeToTable("demo");
        $reply = $cache->readHash("demo", "63993f21a85dc22e8585", true);
        $this->assertSame(false, is_array($reply), "expected null reply but got an array :(");
        $this->assertSame(null, $reply, "reply should be null at this point");
    }

    /**
     * @depends testMakeHash
     */
    public function testLastChangedTimeButSameVersionNumber()
    {
        $cache = $this->getCache();
        $cache->addTableToCache("demo", 10, true, true, false);
        $cache->forceAdjustTablesLastChanged("demo", "time", time() - 20);
        $cache->forceAdjustTablesLastChanged("demo", "version", 1);
        $reply = $cache->writeHash("demo", "63993f21a85dc22e8585", ["unittest" => "yes"], true);
        $this->assertSame(true, $reply->status, "write hash failed: " . $reply->message);
        $cache->save();
        $cache = $this->getCache();
        $cache->addTableToCache("demo", 10, true, true, false);
        $cache->forceAdjustTablesLastChanged("demo", "time", 4824014434);
        $cache->forceAdjustTablesLastChanged("demo", "version", 1);
        $reply = $cache->readHash("demo", "63993f21a85dc22e8585", true);
        $this->assertSame(false, is_array($reply), "expected null reply but got an array :(");
        $this->assertSame(null, $reply, "reply should be null at this point");
        $cache->forceAdjustTablesLastChanged("demo", "version", 7654);
        $cache->save();
    }

    /**
     * @depends testLastChangedTimeButSameVersionNumber
     */
    public function testRestoreLastChangedAfterSave()
    {
        $cache = $this->getCache();
        $reply = $cache->gettablesLastChanged();
        $this->assertSame(true, is_array($reply), "expected last changed to be an array");
        $this->assertSame(true, array_key_exists("demo", $reply), "expected to load table demo into last changed after starting worker");
        $this->assertSame(4824014434, $reply["demo"]["time"], "expected last changed time to be 2122/11/13 @ 12:00:34");
        $this->assertSame(7654, $reply["demo"]["version"], "expected version number is not correct");
    }

    /**
     * @depends testRestoreLastChangedAfterSave
     */
    public function testObjectUsesCache()
    {
        global $system;
        $system = new Config();
        $cache = $this->getCache();
        $Counttoonehundo = new Counttoonehundo();
        $cache->addTableToCache($Counttoonehundo->getTable(), 15, true, true, false);
        $Counttoonehundo->attachCache($cache);
        $stats = $cache->getStats();
        $this->assertSame(0, $stats->reads, "reads counter is wrong :/");
        $this->assertSame(0, $stats->writes, "writes counter is wrong :/");
        $this->assertSame(0, $stats->miss, "miss counter is wrong :/");
        $load = $Counttoonehundo->loadId(1);
        $this->assertSame(true, $load->status, "Failed to load from DB: " . $load->message);
        $stats = $cache->getStats();
        $this->assertSame(0, $stats->reads, "reads counter is wrong :/");
        $this->assertSame(1, $stats->miss, "miss counter is wrong :/");
        $this->assertSame(1, $stats->writes, "writes counter is wrong :/");
    }

    /**
     * @depends testReadUpdateVersionChanges
     */
    public function testDeleteHash()
    {
        $cache = $this->getCache();
        $cache->addTableToCache("fuckifiknow", 15, true, true);
        $result = $cache->writeHash("fuckifiknow", "woof", ["pend" => true], true);
        $this->assertSame(true, $result->status, "Expected to write hash to cache");
        $cache->shutdown(true);
        $cache = $this->getCache();
        $keys = $cache->getDriver()->listKeys()->keys;
        $this->assertSame(true, in_array("woof", $keys), "Expected woof key in cache");
        $cache->addTableToCache("fuckifiknow", 15, true, true);
        $this->assertSame(0, $cache->getStats()->deletes, "expected zero deletes here");
        $result = $cache->deleteHash("fuckifiknow", "woof", true);
        $this->assertSame(1, $cache->getStats()->deletes, "expected a delete here");
        $this->assertSame(true, $result->status, "Expected to delete hash from cache");
        $cache->shutdown(true);
        $cache = $this->getCache();
        $keys = $cache->getDriver()->listKeys()->keys;
        $this->assertSame(false, in_array("woof", $keys), "Expected woof key to no longer be in cache");
        $cache->shutdown(true);
    }

    /**
     * @depends testObjectUsesCache
     */
    public function testVersionChanges()
    {
        $this->purgeCache();
        $cache = $this->getCache();
        $cache->forceAdjustTablesLastChanged("test.counttoonehundo", "time", time() - 20);
        $cache->forceAdjustTablesLastChanged("test.counttoonehundo", "version", 1);
        $version = $cache->gettablesLastChanged();
        $this->assertSame(1, $version["test.counttoonehundo"]["version"], "version setup bad");
        $Counttoonehundo = new Counttoonehundo();
        $cache->addTableToCache($Counttoonehundo->getTable(), 15, true, true, false);
        $Counttoonehundo->attachCache($cache);
        $Counttoonehundo->loadId(1);
        $Counttoonehundo->setCvalue($Counttoonehundo->getCvalue() + 5);
        $version = $cache->gettablesLastChanged();
        $this->assertSame(1, $version["test.counttoonehundo"]["version"], "version setup bad");
        $reply = $Counttoonehundo->updateEntry();
        $this->assertSame(true, $reply->status, "Failed to update entry: " . $reply->message);
        $this->assertSame("ok", $reply->message, "Expected update here");
        $this->assertSame("updated version 1 => 2", $cache->getLastErrorBasic(), "failed to update version");
        $this->assertSame(true, $cache->shutdown(), "failed to write changes to db: " . $cache->getLastErrorBasic());
        $this->assertSame("stopping driver", $cache->getLastErrorBasic(), "incorrect cache shutdown message");
        $cache = $this->getCache();
        $version = $cache->gettablesLastChanged();
        $this->assertSame(2, $version["test.counttoonehundo"]["version"], "version setup bad");
        $this->assertSame("All keys deleted", $cache->purge()->message, "Failed to purge cache");
    }

    /**
     * @depends testVersionChanges
     */
    public function testReadUpdateVersionChanges()
    {
        $this->purgeCache();
        $cache = $this->getCache();
        $cache->forceAdjustTablesLastChanged("test.counttoonehundo", "time", time() - 20);
        $cache->forceAdjustTablesLastChanged("test.counttoonehundo", "version", 1);
        $version = $cache->gettablesLastChanged();
        $startedVersion = $version["test.counttoonehundo"]["version"];
        $this->assertSame(1, $startedVersion, "Expected starting version number to be 1");
        $loop = 1;
        while ($loop < 4) {
            $Counttoonehundo = new Counttoonehundo();
            $cache->addTableToCache($Counttoonehundo->getTable(), 15, true, true, false);
            $Counttoonehundo->attachCache($cache);
            $Counttoonehundo->loadId(1);
            $Counttoonehundo->setCvalue($Counttoonehundo->getCvalue() + 5);
            $Counttoonehundo->updateEntry();
            $version = $cache->gettablesLastChanged();
            $this->assertSame($startedVersion + $loop, $version["test.counttoonehundo"]["version"], "Expected starting version number to change");
            $loop++;
        }
    }

    protected function purgeCache()
    {
        $cache = $this->getCache();
        $this->assertSame("All keys deleted", $cache->purge()->message, "Failed to purge cache");
        $cache->shutdown(true);
    }
}
