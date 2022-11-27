<?php

namespace YAPF\Framework\Generator;

class ModelFactory extends GeneratorWriter
{
    protected function createFromTable(string $database, string $table, array $fkLink): void
    {
        if ($this->use_output == true) {
            if ($this->console_output == true) {
                echo "Table: " . $table . " ~ ";
            } else {
                $this->output .= "<td>" . $table . "</td>";
            }
        }
        $cols = $this->getTableColumns($database, $table);
        if ($this->use_output == true) {
            if ($this->console_output == true) {
                echo "Single: ";
            }
        }
        $this->createSingle($database, $table, $cols, $fkLink);
        if ($this->use_output == true) {
            if ($this->console_output == true) {
                echo "Set: ";
            }
        }
        $this->createSet($database, $table, $cols, $fkLink);
        if ($this->use_output == true) {
            if ($this->console_output == true) {
                echo "\n";
            }
        }
    }

    protected function createSet(string $database, string $table, array $cols, array $links): void
    {
        global $GEN_SET_NS, $GEN_SOLO_NS, $GEN_SET_PATH, $GEN_PREFIX_TABLE;
        $class_name = ucfirst(strtolower($table));
        $set = new SetModelFactory(
            $class_name,
            $GEN_SOLO_NS,
            $GEN_SET_NS,
            $database,
            $table,
            $cols,
            $links,
            $GEN_PREFIX_TABLE
        );

        $filename = $class_name . "Set.php";

        $this->writeFile($this->lines2text($set->getLines()), $filename, $GEN_SET_PATH);
        $this->countRelatedActions += $set->getRelatedCounter();
    }

    protected function createSingle(string $database, string $table, array $cols, array $links): void
    {
        global $GEN_SET_NS, $GEN_SOLO_NS, $GEN_SOLO_PATH, $GEN_PREFIX_TABLE;
        $class_name = ucfirst(strtolower($table));

        $single = new SingleModelFactory(
            $class_name,
            $GEN_SOLO_NS,
            $GEN_SET_NS,
            $database,
            $table,
            $cols,
            $links,
            $GEN_PREFIX_TABLE
        );

        $filename = $class_name . ".php";

        $this->writeFile($this->lines2text($single->getLines()), $filename, $GEN_SOLO_PATH);
        $this->countRelatedActions += $single->getRelatedCounter();
    }

    /**
     * getTableColumns
     * returns the table schema.columns or null
     * @return mixed[] or null
     */
    protected function getTableColumns(string $target_database, string $target_table): ?array
    {
        $whereConfig = [
            "fields" => ["TABLE_SCHEMA", "TABLE_NAME"],
            "matches" => ["=","="],
            "values" => [$target_database, $target_table],
            "types" => ["s", "s"],
        ];
        $basic_config = [
            "table" => "information_schema.columns",
            "fields" => ["COLUMN_NAME","COLUMN_DEFAULT","DATA_TYPE","COLUMN_TYPE"],
        ];
        $results = $this->sql->selectV2($basic_config, null, $whereConfig);
        $returndata = null;
        if ($results->status == true) {
            $returndata = $results->dataset;
        }
        return $returndata;
    }
}
