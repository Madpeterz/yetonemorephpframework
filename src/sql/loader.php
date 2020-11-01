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
include $mysqliLoadPath . "Update.php";
include $mysqliLoadPath . "Remove.php";
include $mysqliLoadPath . "Select.php";
include $mysqliLoadPath . "Count.php";
include $mysqliLoadPath . "Search.php";
include $mysqliLoadPath . "Enabled.php";
