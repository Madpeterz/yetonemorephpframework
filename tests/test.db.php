<?php

namespace App;

use YAPF\Core\ErrorLogging as ErrorLogging;

class Db extends ErrorLogging
{
    protected $dbHost = "127.0.0.1";
    public $dbName = "test";
    protected $dbUser = "testsuser";
    protected $dbPass = "testsuserPW";
}
