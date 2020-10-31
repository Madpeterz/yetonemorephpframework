<?php
// this loads after db_objects but before mysqli loader!
use YAPF\ErrorLogging as ErrorLogging;

class db extends ErrorLogging
{
protected $dbHost = "localhost";
protected $dbName = "";
protected $dbUser = "";
protected $dbPass = "";
}
?>
