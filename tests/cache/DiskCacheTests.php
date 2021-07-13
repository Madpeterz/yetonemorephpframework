<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\Cache\DiskCache;
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
        $cache->addTableToCache("testing");
        $cache->start(true);
        $this->assertSame(false,file_exists("tmp/testing.dat"),"Cache file without inf still on disk!");
    }

    public function testReadFromDbAndPutOnDisk(): void
    {
        global $sql;
        $countto = new CounttoonehundoSet();
        $cache = new DiskCache("tmp");
        $cache->addTableToCache($countto->getTable(),10,true);
        $cache->start(true);
        
        $countto->attachCache($cache);
        $cache->addTableToCache($countto->getTable(),10,true);
        $loadResult = $countto->loadAll();
        $this->assertSame(true,$loadResult["status"],"Failed to read from DB");
        $cache->shutdown();

        $cache = new DiskCache("tmp");
        $cache->addTableToCache($countto->getTable(),10,true);
        $cache->start(true);
        
        $this->assertSame(1,$sql->getSQLselectsCount(),"Incorrect number of DB reads");
        $countto = new CounttoonehundoSet();
        $loadResult = $countto->loadAll();
        $this->assertSame(true,$loadResult["status"],"Failed to read from DB (step 2)");
        $this->assertSame(1,$sql->getSQLselectsCount(),"Incorrectly loaded from the DB and not from cache");
    }

}