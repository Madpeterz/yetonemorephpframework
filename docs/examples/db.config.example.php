<?php

namespace App;

use YAPF\Core\ErrorControl\ErrorLogging as ErrorLogging;

class Db extends ErrorLogging
{
    protected $dbHost = "localhost";
    protected ?string $dbName = "";
    protected $dbUser = "";
    protected $dbPass = "";
}
