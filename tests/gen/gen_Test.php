<?php

namespace YAPFtest;

use PHPUnit\Framework\TestCase;
use YAPF\Framework\Generator\DbObjects;
use YAPF\Framework\MySQLi\MysqliEnabled as MysqliConnector;

class GeneratorTest extends TestCase
{
    /* @var YAPF\Framework\MySQLi\MysqliEnabled $sql */
    protected ?MysqliConnector $sql;
    protected $db_objects_factory = null;
    protected function setUp(): void
    {
        $this->sql = new MysqliConnector();
    }
    protected function tearDown(): void
    {
        $this->sql->sqlSave(true);
        $this->sql = null;
    }

    public function ResetDbFirst()
    {
        $results = $this->sql->rawSQL("tests/testdataset.sql");
        // [status =>  bool, message =>  string]
        $this->assertSame("ok", $results->message, "incorrect message");
        $this->assertSame($results->status, true);
        $this->assertSame($results->commandsRun, 68, "incorrect num of commands");
    }

    public function testCreateModels()
    {
        $this->ResetDbFirst();
        $db_objects_factory = new DbObjects(
            ["test","key2name","website"], 
            "YAPF/Junk/<!DBName!>(Set)", 
            "src/Junk/<!DBName!>/(Set)"
        );
        $stats = $db_objects_factory->getStats();
        $this->assertSame(" |  |  | ", $db_objects_factory->getLog(), "log is not correct");
        $this->assertSame(false, $stats["error"], "had a issue writing files");
        $this->assertSame(32, $stats["files"], "had a issue writing files total count");
        $this->assertSame(4712, $stats["lines"], "had a issue writing files total lines");
        
    }
}
