<?php

namespace YAPFtest;

use App\Config;
use PHPUnit\Framework\TestCase;
use YAPF\Junk\Sets\CounttoonehundoSet;

class DbLoggingTests extends TestCase
{
	protected function setUp(): void
    {
        global $system;
        $system = new Config();
    }
    protected function tearDown(): void
    {
        global $system;
        $system->getSQL()->sqlSave(true);
    }
	public function testEnableLogging(): void
	{
		global $system;
		$this->assertTrue($system->getSQL()->setLogQueries(true),"Failed to enable logging");
	}
	public function testDisableLogging(): void
	{
		global $system;
		$this->assertFalse($system->getSQL()->setLogQueries(false),"Failed to disable logging");
	}
	public function testSelectLogging(): void
	{
		global $system;
		$system->getSQL()->setLogQueries(true);
		$system->getSQL()->directSelectSQL("SELECT 1 as test");
		$log = $system->getSQL()->getQueryLog();
		$this->assertEquals(1,$log["select"]["count"],"Select logging count incorrect");
		$this->assertEquals(1,count($log["select"]["queries"]),"Select logging queries count incorrect");
		$this->assertEquals(1,$log["select"]["rows"],"Select logging rows incorrect");
	}
	public function testSelectLoggingViaDbObject(): void
	{
		global $system;
		$system->getSQL()->setLogQueries(true);
		
		$countSet = new CounttoonehundoSet();
		$countSet->loadAll();

		$log = $system->getSQL()->getQueryLog();
		$this->assertEquals(1,$log["select"]["count"],"Select logging count incorrect");
		$this->assertEquals(1,count($log["select"]["queries"]),"Select logging queries count incorrect");
		$this->assertEquals(100,$log["select"]["rows"],"Select logging rows incorrect");
		$this->assertSame(
			"SELECT * FROM test.counttoonehundo  ORDER BY id ASC",
			$log["select"]["queries"][0]["sql"],
			"Select logging sql incorrect");
		$this->assertIsFloat($log["select"]["queries"][0]["time"],"Select logging time incorrect");
	}
	public function testSelectLoggingViaDbObjectMultipleQuerys(): void
	{
		global $system;
		$system->getSQL()->setLogQueries(true);
		
		$countSet = new CounttoonehundoSet();
		$countSet->loadAll();
		$countSet = new CounttoonehundoSet();
		$countSet->loadAll();
		$countSet = new CounttoonehundoSet();
		$countSet->loadAll();

		$log = $system->getSQL()->getQueryLog();
		$this->assertEquals(3,$log["select"]["count"],"Select logging count incorrect");
		$this->assertEquals(3,count($log["select"]["queries"]),"Select logging queries count incorrect");
		$this->assertEquals(300,$log["select"]["rows"],"Select logging rows incorrect");
		$this->assertSame(
			"SELECT * FROM test.counttoonehundo  ORDER BY id ASC",
			$log["select"]["queries"][0]["sql"],
			"Select logging sql incorrect");
		$this->assertIsFloat($log["select"]["queries"][0]["time"],"Select logging time incorrect");
	}
}