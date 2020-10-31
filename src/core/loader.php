<?php

if (isset($coreLoadPath) == false) {
    $coreLoadPath = dirname(__FILE__) . "/";
}

if (defined("REQUIRE_ID_ON_LOAD") == false) {
    include $coreLoadPath . "SetRequireID.php";
}

include $coreLoadPath . "ErrorLogging.php";
include $coreLoadPath . "SqlConnectedClass.php";
