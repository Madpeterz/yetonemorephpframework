<?php
class db extends error_logging
{
    protected $dbHost = gen_database_host;
    public $dbName = "";
    protected $dbUser = gen_database_username;
    protected $dbPass = gen_database_password;
}
?>
