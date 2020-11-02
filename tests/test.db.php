<?php

namespace App;

use YAPF\Core\ErrorLogging as ErrorLogging;

class Db extends ErrorLogging
{
    protected $dbHost = "localhost";
    protected $dbName = "test";
    protected $dbUser = "testsuser";
    protected $dbPass = "testsuserPW";
}
