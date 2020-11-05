<?php

namespace YAPF\Generator;

class ModelFactory extends GeneratorWriter
{
    protected function createModel(string $target_table, string $target_database): void
    {
        $this->found_id = false;
        $class_name = ucfirst(strtolower($target_table));
        if ($this->use_output == true) {
            echo "<tr><td>" . $class_name . "</td>";
        }
        $results = $this->getTableColumns($target_table, $target_database);
        if ($results == null) {
            $this->addError(__FILE__, __FUNCTION__, "Error ~ Unable to get fields");
            if ($this->use_output == true) {
                echo "<td>Error</td><td>Unable to get fields</tr>";
            }
            return;
        }
        $this->file_lines = [];
        $this->createCollectionSetFile($class_name, $target_database, $target_table);
        $create_file = GEN_SAVE_MODELS_TO . $target_table . "Set.php";
        if ($this->use_output == true) {
            echo "<td>";
        }
        $this->writeModelFile($create_file);
        if ($this->use_output == true) {
            echo "</td>";
        }
        $this->file_lines = [];
        $this->createModelHeader($class_name, $target_database, $target_table);
        $this->createModelDataset($target_table, $results);
        $this->createModelGetters($target_table, $results);
        $this->createModelSetters($target_table, $results);
        $this->createModelFooter();
        $create_file = GEN_SAVE_MODELS_TO . $target_table . ".php";
        if ($this->use_output == true) {
            echo "<td>";
        }
        $this->writeModelFile($create_file);
        if ($this->use_output == true) {
            echo "</td></tr>";
        }
    }
    protected function createCollectionSetFile(string $class_name, string $target_table, string $target_database): void
    {
        $add_target_db_to_class = "";
        if (GEN_ADD_DB_TO_TABLE == true) {
            $add_target_db_to_class = $database . ".";
        }

        $this->file_lines[] = '<?php';
        $this->file_lines[] = '';
        $this->file_lines[] = 'namespace App;';
        $this->file_lines[] = '';
        $this->file_lines[] = 'use YAPF\DB_OBJECTS\CollectionSet as CollectionSet;';
        $this->file_lines[] = '';
        $this->file_lines[] = '// Do not edit this file, rerun gen.php to update!';
        $this->file_lines[] = 'class ' . $class_name . 'Set extends CollectionSet';
        $this->file_lines[] = '{';
        $this->file_lines[] = '}';
    }

    protected function createModelFooter(): void
    {
        $this->file_lines[] = [0];
        $this->file_lines[] = '}';
        $this->file_lines[] = '// please do not edit this file';
    }

    protected function createModelSetters(string $target_table, array $data_two): void
    {
        foreach ($data_two as $row_two) {
            if ($row_two["COLUMN_NAME"] != "id") {
                $return_type_addon = "";
                $use_type = $this->getColType(
                    $row_two["DATA_TYPE"],
                    $row_two["COLUMN_TYPE"],
                    $target_table,
                    $row_two["COLUMN_NAME"]
                );
                if ($use_type == "float") {
                    $use_type = "double";
                } elseif ($use_type == "str") {
                    $use_type = "string";
                }
                $return_type_addon = "?" . $use_type . "";
                $set_function = 'public function set' . ucfirst($row_two["COLUMN_NAME"]);
                $set_function .= '(' . $return_type_addon . ' $newvalue): array';
                $this->file_lines[] = $set_function;
                $this->file_lines[] = '{';
                $this->file_lines[] = [2];
                $this->file_lines[] = 'return $this->updateField("' . $row_two["COLUMN_NAME"] . '", $newvalue);';
                $this->file_lines[] = [1];
                $this->file_lines[] = '}';
            }
        }
    }

    protected function createModelGetters(string $target_table, array $data_two): void
    {
        foreach ($data_two as $row_two) {
            if ($row_two["COLUMN_NAME"] != "id") {
                $return_type_addon = "";
                $use_type = $this->getColType(
                    $row_two["DATA_TYPE"],
                    $row_two["COLUMN_TYPE"],
                    $target_table,
                    $row_two["COLUMN_NAME"]
                );
                if ($use_type == "float") {
                    $use_type = "double";
                } elseif ($use_type == "str") {
                    $use_type = "string";
                }
                $return_type_addon = ": ?" . $use_type . "";
                $get_function = 'public function get' . ucfirst($row_two["COLUMN_NAME"]);
                $get_function .= '()' . $return_type_addon;
                $this->file_lines[] = $get_function;
                $this->file_lines[] = '{';
                $this->file_lines[] = [2];
                $this->file_lines[] = 'return $this->getField("' . $row_two["COLUMN_NAME"] . '");';
                $this->file_lines[] = [1];
                $this->file_lines[] = '}';
            }
        }
    }

    protected function createModelDataset(string $target_table, array $data_two): void
    {
        $this->file_lines[] = 'protected $dataset = [';
        $this->file_lines[] = [2];
        foreach ($data_two as $row_two) {
            $use_type = $this->getColType(
                $row_two["DATA_TYPE"],
                $row_two["COLUMN_TYPE"],
                $target_table,
                $row_two["COLUMN_NAME"]
            );
            $detected_default = $row_two["COLUMN_DEFAULT"];
            if (($row_two["COLUMN_DEFAULT"] == null) || ($row_two["COLUMN_DEFAULT"] == "NULL")) {
                $detected_default = "null";
            }
            $line = '"' . $row_two["COLUMN_NAME"] . '" => ["type" => "';
            $line .= $use_type . '", "value" => ' . $detected_default . '],';
            $this->file_lines[] = $line;
        }
        $this->file_lines[] = [1];
        $this->file_lines[] = '];';
    }

    protected function createModelHeader(string $class_name, string $database, string $target_table): void
    {
        $add_target_db_to_class = "";
        if (GEN_ADD_DB_TO_TABLE == true) {
            $add_target_db_to_class = $database . ".";
        }
        $this->file_lines[] = '<?php';
        $this->file_lines[] = '';
        $this->file_lines[] = 'namespace App;';
        $this->file_lines[] = '';
        $this->file_lines[] = 'use YAPF\DB_OBJECTS\GenClass as GenClass;';
        $this->file_lines[] = '';
        $this->file_lines[] = '// Do not edit this file, rerun gen.php to update!';
        $this->file_lines[] = 'class ' . $class_name . ' extends genClass';
        $this->file_lines[] = '{';
        $this->file_lines[] = [1];
        $this->file_lines[] = 'protected $use_table = "' . $add_target_db_to_class . '' . $target_table . '";';
    }
}
