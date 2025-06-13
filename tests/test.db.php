<?php

namespace App;

use YAPF\Core\ErrorControl\ErrorLogging as ErrorLogging;

class Db extends ErrorLogging
{
    public $dbHost = "172.30.225.230";
    public $dbName = "test";
    public $dbUser = "root";
    public $dbPass = "root";
    public function __construct()
    {
    }
}
