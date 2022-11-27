<?php
global $GEN_DATABASE_HOST, $GEN_DATABASE_USERNAME, $GEN_DATABASE_PASSWORD;
global $GEN_PREFIX_TABLE, $GEN_SOLO_PATH, $GEN_DATABASES, $GEN_SOLO_NS;
global $GEN_SET_NS, $GEN_SET_PATH, $GEN_TABLES_ARRAY;

$GEN_DATABASE_HOST = "localhost";
$GEN_DATABASE_USERNAME = "root";
$GEN_DATABASE_PASSWORD = "";

$GEN_PREFIX_TABLE = false; // should gen add the DB to the table name (used if you have multiple databases)
$GEN_DATABASES = ["example"];

$GEN_SOLO_PATH = "../App/Models/"; // the folder path to save single items to
$GEN_SET_PATH = "../App/Models/"; // the folder path to save sets to
$GEN_TABLES_ARRAY = null;
$GEN_SOLO_NS = "App\Models"; // the namespace for single items
$GEN_SET_NS = "App\Models"; // the namespace for sets
