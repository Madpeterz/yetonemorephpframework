<?php

namespace App;

// this loads after db_objects but before mysqli loader!
use YAPF\ErrorLogging as ErrorLogging;

class Db extends ErrorLogging
{
    protected $dbHost = "localhost";
    protected $dbName = "";
    protected $dbUser = "";
    protected $dbPass = "";
}
