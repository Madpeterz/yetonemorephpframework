<?php

namespace YAPF\Junk;

use PHPUnit\Framework\TestCase;
use YAPF\MySQLi\MysqliEnabled as MysqliConnector;

$sql = null;
class DbObjectsSupportTest extends TestCase
{
    /* @var YAPF\MySQLi\MysqliEnabled $sql */
    protected $sql = null;
    protected function setUp(): void
    {
        global $sql;
        define("REQUIRE_ID_ON_LOAD", true);
        $sql = new MysqliConnector();
    }
    protected function tearDown(): void
    {
        global $sql;
        if ($sql != null) {
            $sql->sqlSave(true);
        }
        $sql = null;
    }
    public function testLastSql()
    {
        global $sql;
        $testing = new liketests();
        $this->assertSame($testing->getLastSql(), "");
        $testing->loadID(1);
        $this->assertSame($testing->getLastSql(), "SELECT  * FROM liketests   WHERE id = ? LIMIT 1 ");
    }
    public function testLastSQlWithNullGlobalSql()
    {
        global $sql;
        $sql = null;
        $testing = new liketests();
        $this->assertSame($testing->getLastSql(), "");
    }
}
