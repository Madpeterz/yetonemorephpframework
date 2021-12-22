<?php

namespace YAPF\Generator;

class ModelFactory extends GeneratorWriter
{
    protected function createFromTable(string $database, string $table): void
    {
        $cols = $this->getTableColumns($database, $table);
        $links = $this->getTableLinks($database, $table);
        $this->createSingle($database, $table, $cols, $links);
        $this->createSet($database, $table, $cols, $links);
    }

    protected function createSet(string $database, string $table, array $cols, array $links): void
    {
        global $GEN_NAMESPACE_SET, $GEN_NAMESPACE_SINGLE, $GEN_SAVE_SET_MODELS_TO, $GEN_ADD_DB_TO_TABLE;
        $class_name = ucfirst(strtolower($table));

        $set = new SetModelFactory(
            $class_name,
            $GEN_NAMESPACE_SINGLE,
            $GEN_NAMESPACE_SET,
            $database,
            $table,
            $cols,
            $links,
            $GEN_ADD_DB_TO_TABLE
        );

        $filename = $class_name . "Set.php";

        $this->writeFile($this->lines2text($set->getLines()), $filename, $GEN_SAVE_SET_MODELS_TO);
    }

    protected function createSingle(string $database, string $table, array $cols, array $links): void
    {
        global $GEN_NAMESPACE_SET, $GEN_NAMESPACE_SINGLE, $GEN_SAVE_MODELS_TO, $GEN_ADD_DB_TO_TABLE;
        $class_name = ucfirst(strtolower($table));

        $single = new SingleModelFactory(
            $class_name,
            $GEN_NAMESPACE_SINGLE,
            $GEN_NAMESPACE_SET,
            $database,
            $table,
            $cols,
            $links,
            $GEN_ADD_DB_TO_TABLE
        );

        $filename = $class_name . ".php";

        $this->writeFile($this->lines2text($single->getLines()), $filename, $GEN_SAVE_MODELS_TO);
    }

    /**
     * getTableColumns
     * returns the table schema.columns or null
     * @return mixed[] or null
     */
    protected function getTableColumns(string $target_database, string $target_table): ?array
    {
        $where_config = [
            "fields" => ["TABLE_SCHEMA", "TABLE_NAME"],
            "matches" => ["=","="],
            "values" => [$target_database, $target_table],
            "types" => ["s", "s"],
        ];
        $basic_config = [
            "table" => "information_schema.columns",
            "fields" => ["COLUMN_NAME","COLUMN_DEFAULT","DATA_TYPE","COLUMN_TYPE"],
        ];
        $results = $this->sql->selectV2($basic_config, null, $where_config);
        $returndata = null;
        if ($results["status"] == true) {
            $returndata = $results["dataset"];
        }
        return $returndata;
    }




    /**
     * createRelatedLoaders
     * @return array<string>
     */
    protected function getTableLinks(string $target_database, string $sourceTable): array
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
        $results = $this->sql->selectV2($basic_config, null, $where_config);
        $cnames = [];
        foreach ($results["dataset"] as $entry) {
            if ($entry["TABLE_NAME"] == $sourceTable) {
                $cnames[] = $target_database . "/" . $entry["CONSTRAINT_NAME"];
            }
        }
        if (count($cnames) == 0) {
            return [];
        }
        $where_config = [
            "fields" => ["N_COLS","ID","FOR_NAME"],
            "matches" => ["=","IN","LIKE %"],
            "values" => [1,$cnames,$target_database],
            "types" => ["i","s","s"],
        ];

        $basic_config = [
            "table" => "information_schema.INNODB_SYS_FOREIGN",
            "fields" => ["ID","REF_NAME"],
        ];
        $results = $this->sql->selectV2($basic_config, null, $where_config);
        $links = [];
        $bnames = [];
        foreach ($results["dataset"] as $entry) {
            $bnames[] = $entry["ID"];
            $bits = explode("/", $entry["REF_NAME"]);
            $links[$entry["ID"]] = [
                "target_table" => $bits[1],
                "field_source" => "",
                "field_target" => "",
                "good" => false,
            ];
        }
        if (count($bnames) == 0) {
            return [];
        }
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
        $results = $this->sql->selectV2($basic_config, null, $where_config);
        foreach ($results["dataset"] as $entry) {
            $links[$entry["ID"]]["field_source"] = $entry["FOR_COL_NAME"];
            $links[$entry["ID"]]["field_target"] = $entry["REF_COL_NAME"];
            $links[$entry["ID"]]["good"] = true;
        }
        return $links;
    }
}
