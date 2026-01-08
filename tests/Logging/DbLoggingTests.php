<?php

namespace YAPFtest;

use App\Config;
use PHPUnit\Framework\TestCase;
use YAPF\Junk\Models\Counttoonehundo;
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

	public function testUpdateLogging(): void
	{
		global $system;
		$system->getSQL()->setLogQueries(true);
		$system->getSQL()->rawSQL(null,["UPDATE test.counttoonehundo SET cvalue = 999 WHERE id = 1"]);
		$log = $system->getSQL()->getQueryLog();
		$this->assertEquals(
			1,
			$log["update"]["count"],
			"Update logging count incorrect: ".$system->getSQL()->getLastErrorBasic());
		$this->assertEquals(1,count($log["update"]["queries"]),"Update logging queries count incorrect");
	}
	public function testUpdateLoggingViaDbObject(): void
	{
		global $system;
		$system->getSQL()->setLogQueries(true);
		
		$countSet = new CounttoonehundoSet();
		$countSet->loadAll();
		$count = $countSet->getFirst();
		$count->_Cvalue = $count->_Cvalue + 1;
		$count->updateEntry();

		$log = $system->getSQL()->getQueryLog();
		$this->assertEquals(1,
		$log["update"]["count"],
		"Update logging count incorrect : ".$count->getLastErrorBasic());
		$this->assertEquals(1,count($log["update"]["queries"]),"Update logging queries count incorrect");
		$this->assertIsFloat($log["update"]["queries"][0]["time"],"Update logging time incorrect");
	}
	public function testUpdateLoggingViaDbObjectMultipleQuerys(): void
	{
		global $system;
		$system->getSQL()->setLogQueries(true);
		
		$countSet = new CounttoonehundoSet();
		$countSet->loadAll();
		$count = $countSet->getFirst();
		$count->_Cvalue = $count->_Cvalue + 1;
		$count->updateEntry();
		$count = $countSet->getNext();
		$count->_Cvalue = $count->_Cvalue + 1;
		$count->updateEntry();
		$count = $countSet->getNext();
		$count->_Cvalue = $count->_Cvalue + 1;
		$count->updateEntry();

		$log = $system->getSQL()->getQueryLog();
		$this->assertEquals(3,$log["update"]["count"],"Update logging count incorrect");
		$this->assertEquals(3,count($log["update"]["queries"]),"Update logging queries count incorrect");
		$this->assertIsFloat($log["update"]["queries"][0]["time"],"Update logging time incorrect");
	}

	public function testInsertLogging(): void
	{
		global $system;
		$system->getSQL()->setLogQueries(true);
		$system->getSQL()->rawSQL(null,["INSERT INTO test.counttoonehundo (cvalue) VALUES (1001)"]);
		$log = $system->getSQL()->getQueryLog();
		$this->assertEquals(1,$log["insert"]["count"],"Insert logging count incorrect");
		$this->assertEquals(1,count($log["insert"]["queries"]),"Insert logging queries count incorrect");
	}
	public function testInsertLoggingViaDbObject(): void
	{
		global $system;
		$system->getSQL()->setLogQueries(true);
		
		$count = new Counttoonehundo();
		$count->_Cvalue = 884 + 1;
		$count->createEntry();

		$log = $system->getSQL()->getQueryLog();
		$this->assertEquals(1,$log["insert"]["count"],"Insert logging count incorrect");
		$this->assertEquals(1,count($log["insert"]["queries"]),"Insert logging queries count incorrect");
		$this->assertIsFloat($log["insert"]["queries"][0]["time"],"Insert logging time incorrect");
	}
	public function testInsertLoggingViaDbObjectMultipleQuerys(): void
	{
		global $system;
		$system->getSQL()->setLogQueries(true);
		
		$count = new Counttoonehundo();
		$count->_Cvalue = 1;
		$count->createEntry();
		$count = new Counttoonehundo();
		$count->_Cvalue = 2;
		$count->createEntry();
		$count = new Counttoonehundo();
		$count->_Cvalue = 3;
		$count->createEntry();

		$log = $system->getSQL()->getQueryLog();
		$this->assertEquals(3,$log["insert"]["count"],"Insert logging count incorrect");
		$this->assertEquals(3,count($log["insert"]["queries"]),"Insert logging queries count incorrect");
		$this->assertIsFloat($log["insert"]["queries"][0]["time"],"Insert logging time incorrect");
	}

	public function testDeleteLogging(): void
	{
		global $system;
		$system->getSQL()->setLogQueries(true);
		$system->getSQL()->rawSQL(null,["DELETE FROM test.counttoonehundo WHERE id = 1"]);
		$log = $system->getSQL()->getQueryLog();
		$this->assertEquals(1,$log["delete"]["count"],"Delete logging count incorrect");
		$this->assertEquals(1,count($log["delete"]["queries"]),"Delete logging queries count incorrect");
	}
	public function testDeleteLoggingViaDbObject(): void
	{
		global $system;
		$system->getSQL()->setLogQueries(true);
		
		$countSet = new CounttoonehundoSet();
		$countSet->loadAll();
		$count = $countSet->getFirst();
		$count->removeEntry();

		$log = $system->getSQL()->getQueryLog();
		$this->assertEquals(1,$log["delete"]["count"],"Delete logging count incorrect");
		$this->assertEquals(1,count($log["delete"]["queries"]),"Delete logging queries count incorrect");
		$this->assertIsFloat($log["delete"]["queries"][0]["time"],"Delete logging time incorrect");
	}
	public function testDeleteLoggingViaDbObjectMultipleQuerys(): void
	{
		global $system;
		$system->getSQL()->setLogQueries(true);
		
		$countSet = new CounttoonehundoSet();
		$countSet->loadAll();
		$count = $countSet->getFirst();
		$count->removeEntry();
		$count = $countSet->getNext();
		$count->removeEntry();
		$count = $countSet->getNext();
		$count->removeEntry();

		$log = $system->getSQL()->getQueryLog();
		$this->assertEquals(3,$log["delete"]["count"],"Delete logging count incorrect");
		$this->assertEquals(3,count($log["delete"]["queries"]),"Delete logging queries count incorrect");
		$this->assertIsFloat($log["delete"]["queries"][0]["time"],"Delete logging time incorrect");
	}

	
}