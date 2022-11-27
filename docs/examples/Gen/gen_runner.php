<?php

namespace App;

use YAPF\Framework\Generator\DbObjectsFactory;
use YAPF\Framework\MySQLi\MysqliEnabled;

include "Gen/gen_models_example.config.php";
include "Gen/gen_models_db.php";

// connect to SQL
$sql = new MysqliEnabled();

// lets rock
$db_objects_factory = new DbObjectsFactory();
echo $db_objects_factory->getOutput();
