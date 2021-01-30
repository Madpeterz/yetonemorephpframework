<?php

namespace App;

use YAPF\Core\ErrorLogging as ErrorLogging;

class Db extends ErrorLogging
{
    protected $dbHost = "";
    public $dbName = "";
    protected $dbUser = "";
    protected $dbPass = "";
    public function __construct()
    {
        global $GEN_DATABASE_HOST, $GEN_DATABASE_USERNAME, $GEN_DATABASE_PASSWORD;
        $this->dbHost = $GEN_DATABASE_HOST;
        $this->dbUser = $GEN_DATABASE_USERNAME;
        $this->dbPass = $GEN_DATABASE_PASSWORD;
    }
}
