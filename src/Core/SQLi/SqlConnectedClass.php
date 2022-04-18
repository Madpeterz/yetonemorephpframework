<?php

namespace YAPF\Framework\Core\SQLi;

use YAPF\Core\ErrorControl\ErrorLogging;
use YAPF\Framework\Cache\Cache;
use YAPF\Framework\MySQLi\MysqliEnabled;

abstract class SqlConnectedClass extends ErrorLogging
{
    protected ?MysqliEnabled $sql;
    protected $disabled = false;
    /**
     * __construct
     * if not marked as disabled connects the sql global value
     */
    public function getLastSql(): ?string
    {
        return $this->sql?->getLastSql();
    }
    public function __construct()
    {
        global $system;
        if ($this->disabled == false) {
            $this->sql = $system->getSQL();
            $this->cache = $system->getCacheDriver();
        }
    }

    public function reconnectSql(MysqliEnabled &$SetSQl): void
    {
        if ($this->sql != null) {
            $this->sql = &$this->unRef($this->sql);
        }
        $this->sql = $SetSQl;
    }
    protected function &unRef($var): ?MysqliEnabled
    {
        return $var;
    }

    protected ?Cache $cache = null;
    public function attachCache(Cache &$setCache): void
    {
        if ($this->cache != null) {
            $this->cache = $this->unRefCache($this->cache);
        }
        $this->cache = $setCache;
    }
    protected function &unRefCache($var): ?Cache
    {
        return $var;
    }
}
