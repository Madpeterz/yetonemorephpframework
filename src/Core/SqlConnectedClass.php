<?php

namespace YAPF\Core;

abstract class SqlConnectedClass extends ErrorLogging
{
    protected $sql = null;
    protected $disabled = false;
    /**
     * __construct
     * if not marked as disabled connects the sql global value
     */
    public function getLastSql(): string
    {
        if ($this->sql != null) {
            return $this->sql->getLastSql();
        }
        return "";
    }
    public function __construct()
    {
        global $sql;
        if ($this->disabled == false) {
            $this->sql = &$sql;
        }
    }
    public function reconnectSql(&$SetSQl): void
    {
        $this->sql = $SetSQl;
    }
}
