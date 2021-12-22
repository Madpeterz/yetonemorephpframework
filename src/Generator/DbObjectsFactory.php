<?php

namespace YAPF\Generator;

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
        if ($this->use_output == true) {
            if ($this->console_output == false) {
                $this->output .=  '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/';
                $this->output .=  'bootstrap/4.5.2/css/bootstrap.min.css"';
                $this->output .=  ' integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z"';
                $this->output .=  ' crossorigin="anonymous">';
                $this->output .=  '<link rel="stylesheet" ';
                $this->output .=  'href="https://stackpath.bootstrapcdn.com/bootswatch/4.5.2/darkly/bootstrap.min.css"';
                $this->output .=  ' integrity="sha384-nNK9n28pDUDDgIiIqZ/MiyO3F4/9vsMtReZK39klb/MtkZI3/LtjSjlmyVPS3KdN"';
                $this->output .=  ' crossorigin="anonymous">';
            }
        }
        if (isset($GEN_DATABASES) == true) {
            if (count($GEN_DATABASES) > 0) {
                foreach ($GEN_DATABASES as $gen_database_name) {
                    $this->processDatabaseTables($gen_database_name);
                }
            }
        }
    }

    /**
     * getDBForeignKeys
     * @return array<mixed>
     */
    protected function getDBForeignKeys(string $target_database): array
    {
        $where_config = [
            "fields" => ["TABLE_SCHEMA","CONSTRAINT_TYPE"],
            "matches" => ["=","="],
            "values" => [$target_database,"FOREIGN KEY"],
            "types" => ["s","s"],
        ];
        $basic_config = [
            "table" => "information_schema.table_constraints",
            "fields" => ["CONSTRAINT_NAME","TABLE_NAME"],
        ];
        return $this->sql->selectV2($basic_config, null, $where_config);
    }

    /**
     * getDbFkInfo
     * @return array<mixed>
     */
    protected function getDbFkInfo(string $target_database, array $fknames): array
    {
        $where_config = [
            "fields" => ["N_COLS","ID","FOR_NAME"],
            "matches" => ["=","IN","LIKE %"],
            "values" => [1,$fknames,$target_database],
            "types" => ["i","s","s"],
        ];
        $basic_config = [
            "table" => "information_schema.INNODB_SYS_FOREIGN",
            "fields" => ["ID","REF_NAME"],
        ];

        return $this->sql->selectV2($basic_config, null, $where_config);
    }

    /**
     * getDbFkColInfo
     * @return array<mixed>
     */
    protected function getDbFkColInfo(array $bnames): array
    {
        $where_config = [
            "fields" => ["ID"],
            "matches" => ["IN"],
            "values" => [$bnames],
            "types" => ["s"],
        ];
        $basic_config = [
            "table" => "information_schema.INNODB_SYS_FOREIGN_COLS",
            "fields" => ["ID","FOR_COL_NAME","REF_COL_NAME"],
        ];
        return $this->sql->selectV2($basic_config, null, $where_config);
    }

    /**
     * createRelatedLoaders
     * @return array<string>
     */
    protected function getLinks(string $target_database): array
    {
        $fk = $this->getDBForeignKeys($target_database);
        $fknames = [];
        $fkname2table = [];
        foreach ($fk["dataset"] as $entry) {
            $fname = strtolower($target_database . "/" . $entry["CONSTRAINT_NAME"]);
            $fknames[] = $fname;
            $fkname2table[$fname] = $entry["TABLE_NAME"];
        }
        if (count($fknames) == 0) {
            return [];
        }
        $packet = [];
        $fkinfo = $this->getDbFkInfo($target_database, $fknames);
        $bnames = [];
        foreach ($fkinfo["dataset"] as $entry) {
            // "ID","REF_NAME"
            $idme = strtolower($entry["ID"]);
            if (array_key_exists($idme, $fkname2table) == false) {
                continue;
            }
            $bits = explode("/", $entry["REF_NAME"]);
            if ($bits[0] != $target_database) {
                continue;
            }
            $bnames[] = $entry["ID"];
            $packet[$idme] = [
                "source_table" => $fkname2table[$idme],
                "source_field" => "",
                "target_table" => $bits[1],
                "target_field" => "",
            ];
        }
        $fkcolinfo = $this->getDbFkColInfo($bnames);
        foreach ($fkcolinfo["dataset"] as $entry) {
            $idme = strtolower($entry["ID"]);
            if (array_key_exists($idme, $packet) == false) {
                continue;
            }
            $packet[$idme]["source_field"] = $entry["FOR_COL_NAME"];
            $packet[$idme]["target_field"] = $entry["REF_COL_NAME"];
        }
        return $packet;
    }

    public function processDatabaseTables(string $target_database): void
    {
        global $GEN_SELECTED_TABLES_ONLY;
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
        $where_config = [
            "fields" => ["TABLE_SCHEMA"],
            "matches" => ["="],
            "values" => [$target_database],
            "types" => ["s"],
        ];
        $basic_config = [
            "table" => "information_schema.tables",
            "fields" => ["TABLE_NAME"],
        ];
        $results = $this->sql->selectV2($basic_config, null, $where_config);
        if ($results["status"] == false) {
            if ($this->use_output == true) {
                if ($this->console_output == true) {
                    echo "\033[31mError: Unable to get tables from DB\033[0m\n";
                } else {
                    $this->output .= "<tr><td>Error</td><td>Unable to get tables</td><td>from db</td></tr>";
                }
            }
            $error_msg = "Error ~ Unable to get tables for " . $target_database . "";
            $this->addError(__FILE__, __FUNCTION__, $error_msg);
            return;
        }
        $links = $this->getLinks($target_database);
        foreach ($results["dataset"] as $row) {
            $process = true;
            if (isset($GEN_SELECTED_TABLES_ONLY) == true) {
                if (is_array($GEN_SELECTED_TABLES_ONLY) == true) {
                    $process = in_array($row["TABLE_NAME"], $GEN_SELECTED_TABLES_ONLY);
                }
            }
            if ($process == true) {
                $this->createFromTable($target_database, $row["TABLE_NAME"], $links);
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
