<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Cache\DiskCache;
use YAPF\Junk\Models\Counttoonehundo;
use YAPF\Junk\Sets\CounttoonehundoSet;
use YAPF\MySQLi\MysqliEnabled;

class DiskCacheTests extends TestCase
{
    protected function setUp(): void
    {
        global $sql;
        define("REQUIRE_ID_ON_LOAD", true);
        $sql = new MysqliEnabled();
    }
    protected function tearDown(): void
    {
        global $sql;
        $sql->sqlSave(true);
        $sql = null;
    }

    public function testCreateAndCleanup(): void
    {
        $countto = new CounttoonehundoSet();
        if(is_dir("tmp") == false) {
            mkdir("tmp");
        }
        if(is_dir("tmp/None") == false) {
            mkdir("tmp/None");
        }
        if(file_exists("tmp/None/testing.dat") == false)
        {
            file_put_contents("tmp/None/testing.dat","delete me");
        }
        $cache = new DiskCache("tmp");
        $cache->addTableToCache($countto->getTable(),10,true);
        $cache->start(true);
        $this->assertSame(false,file_exists("tmp/testing.dat"),"Cache file without inf still on disk!");
        $cache->purge();
    }

    /**
     * @depends testCreateAndCleanup
     */
    public function testReadFromDbAndPutOnDiskReadFromDisk(): void
    {
        global $sql;
        $countto = new CounttoonehundoSet();
        $cache = new DiskCache("tmp");
        $cache->addTableToCache($countto->getTable(),10,true);
        $cache->start(true);
        
        $countto->attachCache($cache);
        $loadResult = $countto->loadAll();
        $this->assertSame(1,$sql->getSQLselectsCount(),"");
        $this->assertSame(true,$loadResult["status"],"Failed to read from DB");
        $cache->shutdown();

        $cache = new DiskCache("tmp");
        $cache->addTableToCache($countto->getTable(),10,true);
        $cache->start(true);
        
        $this->assertSame(1,$sql->getSQLselectsCount(),"Incorrect number of DB reads");
        $countto = new CounttoonehundoSet();
        $countto->attachCache($cache);
        $loadResult = $countto->loadAll();
        $this->assertSame(true,$loadResult["status"],"Failed to read from DB (step 2)");
        $this->assertSame(1,$sql->getSQLselectsCount(),"Incorrectly loaded from the DB and not from cache");
    }

    /**
     * @depends testReadFromDbAndPutOnDiskReadFromDisk
     */
    public function testNewConnectionReadFromCache(): void
    {
        global $sql;
        $this->assertSame(0,$sql->getSQLselectsCount(),"DB reads should be zero");

        $countto = new CounttoonehundoSet();
        $cache = new DiskCache("tmp");
        $cache->addTableToCache($countto->getTable(),10,true);
        $cache->start();
        $countto->attachCache($cache);
        $loadResult = $countto->loadAll();
        $this->assertSame(true,$loadResult["status"],"Failed to read from DB (step 2)");
        
        $this->assertSame(0,$sql->getSQLselectsCount(),"DB reads should be zero");
    }

    /**
     * @depends testNewConnectionReadFromCache
     */
    public function testCacheExpiredBecauseChanged(): void
    {
        global $sql;
        //$sql = new MysqliEnabled();
        $this->assertSame(0,$sql->getSQLselectsCount(),"DB reads should be zero");
        $countto = new CounttoonehundoSet();
        $cache = new DiskCache("tmp");
        $cache->addTableToCache($countto->getTable(),10,true);
        $cache->start();
        $countto->attachCache($cache);
        $loadResult = $countto->loadNewest(1);
        $this->assertSame(1,$sql->getSQLselectsCount(),"DB reads should be one");
        $entry = $countto->getFirst();
        $entry->attachCache($cache);
        $entry->setCvalue($entry->getCvalue()+1);
        $result = $entry->updateEntry();
        $this->assertSame(true,$result["status"],"Failed to update entry");
        $sql->sqlSave();
        $cache->shutdown();

        $countto = new CounttoonehundoSet();
        $cache = new DiskCache("tmp");
        $cache->addTableToCache($countto->getTable(),10,true);
        $cache->start();
        $countto->attachCache($cache);
        $loadResult = $countto->loadNewest(1); // cache expired because of change
        $this->assertSame(2,$sql->getSQLselectsCount(),"DB reads should be two");
    }

    /**
     * @depends testCacheExpiredBecauseChanged
     */
    public function testCacheRehitBeforeSave(): void
    {
        /*
            if you load more than once the cache does
            not get hit until after this run has finished.
        */
        global $sql;
        //$sql = new MysqliEnabled();
        $this->assertSame(0,$sql->getSQLselectsCount(),"DB reads should be zero");
        $countto = new CounttoonehundoSet();
        $cache = new DiskCache("tmp");
        $cache->purge();
        $cache->addTableToCache($countto->getTable(),10,true);
        $cache->start();
        $countto->attachCache($cache);
        $countto->loadNewest(1);
        $this->assertSame(1,$sql->getSQLselectsCount(),"DB reads should be one");
        $countto = new CounttoonehundoSet();
        $countto->attachCache($cache);
        $countto->loadNewest(1);
        $this->assertSame(2,$sql->getSQLselectsCount(),"DB reads should be two");
    }


    /**
     * @depends testCacheRehitBeforeSave
     */
    public function testCacheExpired(): void
    {
        global $sql;
        $countto = new CounttoonehundoSet();
        $cache = new DiskCache("tmp");
        $cache->addTableToCache($countto->getTable(),10,true);
        $cache->start();
        $hashid = $this->getCacheHashId($cache);
        $content = '{"changeID":'.$cache->getChangeID("test.counttoonehundo").',"expires":1126197999,"allowChanged":false,"tableName":"test.counttoonehundo"}';
        $cache_file = "tmp/test.counttoonehundo/None/".$hashid.".inf";
        $this->assertSame(true,file_exists($cache_file), "Expected cache file is missing");
        unlink($cache_file);
        file_put_contents($cache_file,$content);

        $this->assertSame(0,$sql->getSQLselectsCount(),"DB reads should be zero");
        $countto = new CounttoonehundoSet();
        $countto->attachCache($cache);
        $countto->loadNewest(1);
        $this->assertSame(1,$sql->getSQLselectsCount(),"DB reads should be one");
    }

    /**
     * @depends testCacheRehitBeforeSave
     */
    public function testAccountHash(): void
    {
        $countto = new CounttoonehundoSet();
        $cache = new DiskCache("tmp");
        $cache->addTableToCache($countto->getTable(),10,false);
        $cache->start();
        $cache->setAccountHash("Magic");
        $hashid = $this->getCacheHashId($cache);
        $countto->attachCache($cache);
        $countto->loadNewest(1);
        $cache->shutdown();
        $cache_file = "tmp/test.counttoonehundo/Magic/".$hashid.".inf";
        $this->assertSame(true,file_exists($cache_file),"expected cache file is missing");
    }

    /**
     * @depends testAccountHash
     */
    public function testCacheFinalPurge(): void
    {
        $countto = new CounttoonehundoSet();
        $cache = new DiskCache("tmp");
        $cache->addTableToCache($countto->getTable(),10,true);
        $cache->start();
        $cache->purge();
        $hashid = $this->getCacheHashId($cache);
        $cache_file = "tmp/test.counttoonehundo/None/".$hashid.".inf";
        $this->assertSame(false,file_exists($cache_file),"Purged file still on disk!");
    }


    protected function getCacheHashId(DiskCache $cache): string
    {
        $singleCount = new Counttoonehundo();
        $where_config = [
            "join_with" => "AND",
             "fields" => [],
             "matches" => [],
             "values" => [],
             "types" => [],
        ];
        $order_config = [
            "ordering_enabled" => true,
            "order_field" => "id",
            "order_dir" => "DESC"
        ];
        $options_config = [
            "page_number" => 0,
            "max_entrys" => 1
        ];
        return $cache->getHash(
            $where_config,
            $order_config,
            $options_config,
            [],
            "test.counttoonehundo",
            count($singleCount->getFields())
        );
    }

}