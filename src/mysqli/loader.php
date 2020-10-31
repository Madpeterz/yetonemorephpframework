<?php
if(isset($mysqliLoadPath) == false)
{
    $mysqliLoadPath = dirname(__FILE__)."/";
}
if(class_exists("db") == false) {
    include $mysqliLoadPath."db.config.example.php";
    trigger_error("No db config loaded",E_USER_NOTICE);
}
include $mysqliLoadPath."core.php";
include $mysqliLoadPath."functions.php";
include $mysqliLoadPath."binds.php";
include $mysqliLoadPath."add.php";
include $mysqliLoadPath."update.php";
include $mysqliLoadPath."remove.php";
include $mysqliLoadPath."select.php";
include $mysqliLoadPath."count.php";
include $mysqliLoadPath."old_binds.php";
include $mysqliLoadPath."custom.php";
include $mysqliLoadPath."shims.php";

class mysqli_controler extends mysqli_shims
{
    // add any custom stuff here
}
?>
