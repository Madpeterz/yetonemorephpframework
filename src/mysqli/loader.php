<?php

if (isset($mysqliLoadPath) == false) {
    $mysqliLoadPath = dirname(__FILE__) . "/";
}
if (class_exists("db") == false) {
    include $mysqliLoadPath . "db.config.example.php";
    trigger_error("No db config loaded", E_USER_NOTICE);
}
include $mysqliLoadPath . "Core.php";
include $mysqliLoadPath . "Functions.php";
include $mysqliLoadPath . "Where.php";
include $mysqliLoadPath . "Options.php";
include $mysqliLoadPath . "Add.php";
include $mysqliLoadPath . "update.php";
include $mysqliLoadPath . "remove.php";
include $mysqliLoadPath . "select.php";
include $mysqliLoadPath . "count.php";
include $mysqliLoadPath . "custom.php";
include $mysqliLoadPath . "mysqli_controler.php";
