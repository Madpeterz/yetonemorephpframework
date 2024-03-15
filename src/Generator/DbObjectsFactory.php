<?php

namespace YAPF\Framework\Generator;

use Fiber;
use YAPF\Framework\Responses\MySQLi\SelectReply;

class DbObjectsFactory extends ModelFactory
{
    public function __construct($autoStart = true)
    {
        parent::__construct();
        if ($autoStart == true) {
            $this->start();
        }
    }
    public function setOutputToHTML(): void
    {
        $this->use_output = true;
        $this->console_output = false;
    }
    public function start(): void
    {
        global $GEN_DATABASES;
        if (($this->use_output == true) && ($this->console_output == false)) {
            $this->output .=  '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/';
            $this->output .=  'bootstrap/4.5.2/css/bootstrap.min.css"';
            $this->output .=  ' integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z"';
            $this->output .=  ' crossorigin="anonymous">';
            $this->output .=  '<link rel="stylesheet" ';
            $this->output .=  'href="https://stackpath.bootstrapcdn.com/bootswatch/4.5.2/darkly/bootstrap.min.css"';
            $this->output .=  ' integrity="sha384-nNK9n28pDUDDgIiIqZ/MiyO3F4/9vsMtReZK39klb/MtkZI3/LtjSjlmyVPS3KdN"';
            $this->output .=  ' crossorigin="anonymous">';
        }
        if (isset($GEN_DATABASES) == true) {
            if (count($GEN_DATABASES) > 0) {
                $fibers = [];
                foreach ($GEN_DATABASES as $gen_database_name) {
                    $fiber = new Fiber(function (string $startArg): void {
                        $this->processDatabaseTables($startArg);
                    });
                    $fiber->start($gen_database_name);
                    $fibers[] = $fiber;
                }
            }
        }
    }

    /**
     * getDBForeignKeys
     */
    protected function getDBForeignKeys(string $target_database): SelectReply
    {
        $whereConfig = [
            "fields" => ["REFERENCED_TABLE_NAME", "TABLE_SCHEMA", "REFERENCED_TABLE_SCHEMA"],
            "values" => [null, $target_database, $target_database],
            "matches" => ["IS NOT", "=", "="],
            "types" => ["s", "s", "s"],
        ];

        $basic_config = [
            "table" => "INFORMATION_SCHEMA.KEY_COLUMN_USAGE",
            "fields" => ["TABLE_NAME", "COLUMN_NAME", "REFERENCED_COLUMN_NAME", "REFERENCED_TABLE_NAME"],
        ];

        return $this->sql->selectV2($basic_config, null, $whereConfig);
    }

    /**
     * createRelatedLoaders
     * @return array<string>
     */
    protected function getLinks(string $target_database): array
    {
        $packet = [];
        foreach ($this->getDBForeignKeys($target_database)->dataset as $entry) {
            $idme = strtolower($entry["TABLE_NAME"] . $entry["COLUMN_NAME"]);
            if (array_key_exists($idme, $packet) == true) {
                continue;
            }
            $packet[] = [
                "source_table" => $entry["TABLE_NAME"],
                "source_field" => $entry["COLUMN_NAME"],
                "target_table" => $entry["REFERENCED_TABLE_NAME"],
                "targetField" => $entry["REFERENCED_COLUMN_NAME"],
            ];
        }
        return $packet;
    }

    public function processDatabaseTables(string $target_database): void
    {
        global $GEN_TABLES_ARRAY;
        $this->sql->dbName = $target_database;
        if ($this->use_output == true) {
            if ($this->console_output == true) {
                echo "Starting database: " . $target_database . "\n";
            } else {
                $this->output .= "<h4>database: " . $target_database . "</h4>";
                $this->output .= "<table class=\"table\"><thead><tr><th>Table</th>";
                $this->output .= "<th>Set</th><th>Single</th></tr></thead><tbody>";
            }
        }
        $whereConfig = [
            "fields" => ["TABLE_SCHEMA"],
            "matches" => ["="],
            "values" => [$target_database],
            "types" => ["s"],
        ];
        $basic_config = [
            "table" => "information_schema.TABLES",
            "fields" => ["TABLE_NAME"],
        ];
        $results = $this->sql->selectV2($basic_config, null, $whereConfig);
        if ($results->status == false) {
            if ($this->use_output == true) {
                if ($this->console_output == true) {
                    echo "\033[31mError: Unable to get tables from DB\033[0m\n";
                } else {
                    $this->output .= "<tr><td>Error</td><td>Unable to get tables</td><td>from db</td></tr>";
                }
            }
            $error_msg = "Error ~ Unable to get tables for " . $target_database . "";
            $this->addError($error_msg);
            return;
        }
        $links = $this->getLinks($target_database);
        $fibers = [];
        foreach ($results->dataset as $row) {
            $process = true;
            if (isset($GEN_TABLES_ARRAY) == true) {
                if (is_array($GEN_TABLES_ARRAY) == true) {
                    $process = in_array($row["TABLE_NAME"], $GEN_TABLES_ARRAY);
                }
            }
            if ($process == true) {
                $fiber = new Fiber(function (string $arg1, string $arg2, array $arg3): void {
                    $this->createFromTable($arg1, $arg2, $arg3);
                });
                $fiber->start($target_database, $row["TABLE_NAME"], $links);
                $fibers[] = $fiber;
            } else {
                if ($this->console_output == true) {
                    echo "Skipped table: " . $row["TABLE_NAME"] . "\n";
                } else {
                    $this->output .= "<tr><td>" . $row["TABLE_NAME"] . "</td><td>Skipped</td><td>Skipped</td></tr>";
                }
            }
        }
        if ($this->use_output == true) {
            if ($this->console_output == true) {
                echo "finished database \n";
            } else {
                $this->output .= "</tbody></table>";
            }
        }
    }
}
