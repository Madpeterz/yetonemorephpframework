<?php

namespace App;

use YAPF\MySQLi\MysqliEnabled as MysqliConnector;
use YAPF\Generator\DbObjectsFactory as DbObjectsFactory;

include "Gen/gen_models_example.config.php";
include "Gen/gen_models_db.php";

// connect to SQL
$sql = new MysqliConnector();

// lets rock
$db_objects_factory = new DbObjectsFactory();
echo $db_objects_factory->getOutput();
