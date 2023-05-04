<?php

namespace App;

use Throwable;
use mysqli;
use YAPF\Framework\Config\SimpleConfig;

/*
    See spec.txt
    for load order for testing
*/
error_reporting(E_ALL & ~E_NOTICE & ~E_USER_NOTICE);

include("vendor/autoload.php");
include("tests/test.db.php");

class Config extends SimpleConfig
{

}
$system = new Config();
