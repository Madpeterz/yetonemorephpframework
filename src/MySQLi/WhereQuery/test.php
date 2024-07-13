<?php

use YAPF\Framework\MySQLi\WhereQuery\WhereQuery;

$where = new WhereQuery();
$where->field()->greaterThan(4);
$where->and()->field()->equalTo(true);

$whereConfig = [
    "fields" => ["id", "banned"],
    "values" => [1, false],
    "matches" => [">", "IS"],
    "types" => ["i", "i"],
    "joinWith" => ["OR", ""],
];
