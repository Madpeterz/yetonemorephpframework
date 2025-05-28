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
        $this->assertSame($results->commandsRun, 57);
    }

    public function testCreateModels()
    {
        $this->ResetDbFirst();
        $db_objects_factory = new DbObjects(["test"], "YAPF/Junk/<!DBName!>(Set)", "src/Junk/<!DBName!>/(Set)");
        $stats = $db_objects_factory->getStats();
        $this->assertSame(false, $stats["error"], "had a issue writing files");
        $this->assertSame(28, $stats["files"], "had a issue writing files total count");
        $this->assertSame(4134, $stats["lines"], "had a issue writing files total lines");
        $this->assertSame("Write log:  | ", $db_objects_factory->getLog(), "log is not correct");
    }
}
