<?php

namespace YAPF\Generator;

class ModelFactory extends GeneratorWriter
{
    /**
     * getTableColumns
     * returns the table schema.columns or null
     * @return mixed[] or null
     */
    protected function getTableColumns(string $target_table, string $target_database): ?array
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
    protected function createModel(string $target_table, string $target_database): void
    {
        global $GEN_SAVE_MODELS_TO, $GEN_SAVE_SET_MODELS_TO;
        $this->found_id = false;
        $class_name = ucfirst(strtolower($target_table));
        if ($this->use_output == true) {
            if ($this->console_output == true) {
                echo "starting class " . $class_name;
            } else {
                $this->output .=  "<tr><td>" . $class_name . "</td>";
            }
        }
        $results = $this->getTableColumns($target_table, $target_database);
        if ($results != null) {
            $this->file_lines = [];
            $this->createCollectionSetFile($class_name, $target_database, $target_table, $results);
            $create_file = $GEN_SAVE_SET_MODELS_TO . $class_name . "Set.php";
            if ($this->use_output == true) {
                if ($this->console_output == true) {
                    echo " - Set: ";
                } else {
                    $this->output .=  "<td>";
                }
            }
            $this->writeModelFile($create_file);
            if ($this->use_output == true) {
                if ($this->console_output == true) {
                    echo " - Single: ";
                } else {
                    $this->output .=  "</td>";
                }
            }
            $this->file_lines = [];
            $this->createModelHeader($class_name, $target_database, $target_table);
            $this->createModelDataset($target_table, $results);
            $this->createModelGetters($target_table, $results);
            $this->createModelSetters($target_table, $results);
            $this->createModelLoaders($target_table, $results);
            $this->createModelFooter();
            $create_file = $GEN_SAVE_MODELS_TO . $class_name . ".php";
            if ($this->use_output == true) {
                if ($this->console_output == true) {
                    echo " - ";
                } else {
                    $this->output .=  "<td>";
                }
            }
            $this->writeModelFile($create_file);
            if ($this->use_output == true) {
                if ($this->console_output == true) {
                    echo " \n ";
                } else {
                    $this->output .=  "</td></tr>";
                }
            }
        }
    }
    protected function createCollectionSetFile(
        string $class_name,
        string $target_table,
        string $target_database,
        array $results
    ): void {
        global $GEN_NAMESPACE_SET, $GEN_NAMESPACE_SINGLE;

        $this->file_lines[] = '<?php';
        $this->file_lines[] = '';
        $this->file_lines[] = 'namespace ' . $GEN_NAMESPACE_SET . ';';
        $this->file_lines[] = '';
        $this->file_lines[] = 'use YAPF\DbObjects\CollectionSet\CollectionSet as CollectionSet;';
        $this->file_lines[] = 'use ' . $GEN_NAMESPACE_SINGLE . '\\' . $class_name . ' as ' . $class_name . ';';
        $this->file_lines[] = '';
        $this->file_lines[] = '// Do not edit this file, rerun gen.php to update!';
        $this->file_lines[] = 'class ' . $class_name . 'Set extends CollectionSet';
        $this->file_lines[] = '{';
        $this->file_lines[] = [1];
        $this->file_lines[] = 'public function __construct()';
        $this->file_lines[] = '{';
        $this->file_lines[] = [2];
        $this->file_lines[] = 'parent::__construct("' . $GEN_NAMESPACE_SINGLE . '\\' . $class_name . '");';
        $this->file_lines[] = [1];
        $this->file_lines[] = '}';
        $this->file_lines[] = [1];
        $this->file_lines[] = '/**';
        $this->file_lines[] = ' * getObjectByID';
        $this->file_lines[] = ' * returns a object that matchs the selected id';
        $this->file_lines[] = ' * returns null if not found';
        $this->file_lines[] = ' * Note: Does not support bad Ids please use findObjectByField';
        $this->file_lines[] = ' */';
        $this->file_lines[] = 'public function getObjectByID($id): ?' . $class_name . '';
        $this->file_lines[] = '{';
        $this->file_lines[] = [2];
        $this->file_lines[] = 'return parent::getObjectByID($id);';
        $this->file_lines[] = [1];
        $this->file_lines[] = '}';
        $this->file_lines[] = '/**';
        $this->file_lines[] = ' * getFirst';
        $this->file_lines[] = ' * returns the first object in a collection';
        $this->file_lines[] = ' */';
        $this->file_lines[] = 'public function getFirst(): ?' . $class_name . '';
        $this->file_lines[] = '{';
        $this->file_lines[] = [2];
        $this->file_lines[] = 'return parent::getFirst();';
        $this->file_lines[] = [1];
        $this->file_lines[] = '}';
        $this->file_lines[] = '/**';
        $this->file_lines[] = ' * getObjectByField';
        $this->file_lines[] = ' * returns the first object in a collection that matchs the field and value checks';
        $this->file_lines[] = ' */';
        $this->file_lines[] = 'public function getObjectByField(string $fieldname, $value): ?' . $class_name . '';
        $this->file_lines[] = '{';
        $this->file_lines[] = [2];
        $this->file_lines[] = 'return parent::getObjectByField($fieldname, $value);';
        $this->file_lines[] = [1];
        $this->file_lines[] = '}';
        $this->file_lines[] = '/**';
        $this->file_lines[] = ' * current';
        $this->file_lines[] = ' * used by foreach to get the object should not be called directly';
        $this->file_lines[] = ' */';
        $this->file_lines[] = 'public function current(): ' . $class_name . '';
        $this->file_lines[] = '{';
        $this->file_lines[] = [2];
        $this->file_lines[] = 'return parent::current();';
        $this->file_lines[] = [1];
        $this->file_lines[] = '}';
        $this->createModelLoaders(
            $target_table,
            $results,
            "array",
            true,
            true
        );
        $this->file_lines[] = [0];
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
        $this->file_lines[] = "// Setters";
        foreach ($data_two as $row_two) {
            if ($row_two["COLUMN_NAME"] != "id") {
                $return_type_addon = "";
                $use_type = $this->getColType(
                    $row_two["DATA_TYPE"],
                    $row_two["COLUMN_TYPE"],
                    $target_table,
                    $row_two["COLUMN_NAME"]
                );
                if ($use_type == "str") {
                    $use_type = "string";
                }
                $return_type_addon = "?" . $use_type . "";
                $this->file_lines[] = "/**";
                $this->file_lines[] = "* set" . ucfirst($row_two["COLUMN_NAME"]);
                $this->file_lines[] = "* @return mixed[] [status =>  bool, message =>  string]";
                $this->file_lines[] = "*/";
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

    protected function createModelLoaders(
        string $target_table,
        array $data_two,
        string $returnType = "bool",
        bool $enableLimits = false,
        bool $includeListLoaders = false
    ): void {
        $this->file_lines[] = "// Loaders";
        foreach ($data_two as $row_two) {
            if ($row_two["COLUMN_NAME"] != "id") {
                $use_type = $this->getColType(
                    $row_two["DATA_TYPE"],
                    $row_two["COLUMN_TYPE"],
                    $target_table,
                    $row_two["COLUMN_NAME"]
                );
                if ($use_type == "str") {
                    $use_type = "string";
                }
                $functionloadname = 'loadBy' . ucfirst($row_two["COLUMN_NAME"]);
                $load_function = 'public function ' . $functionloadname;
                $functionSetup = '(' . $use_type . ' $' . $row_two["COLUMN_NAME"] . '): ' . $returnType . '';
                if ($enableLimits == true) {
                    $functionSetup = '(
        ' . $use_type . ' $' . $row_two["COLUMN_NAME"] . ',
        int $limit = 0,
        string $orderBy = "id",
        string $orderDir = "DESC"
    ): ' . $returnType;
                }
                $load_function .= $functionSetup;
                if ($returnType == "array") {
                    $this->file_lines[] = "/**";
                    $this->file_lines[] = " * " . $functionloadname;
                    $joined = " * " . "@return mixed[] ";
                    $joined .= "[status =>  bool, count => integer, message =>  string]";
                    $this->file_lines[] = $joined;
                    $this->file_lines[] = "*/";
                }
                $this->file_lines[] = $load_function . " { ";
                $this->file_lines[] = [2];
                $finalLine = 'return $this->loadByField("' . $row_two["COLUMN_NAME"] . '", $' . $row_two["COLUMN_NAME"];
                if ($enableLimits == true) {
                    $finalLine .= ', $' . 'limit';
                    $finalLine .= ', $' . 'orderBy';
                    $finalLine .= ', $' . 'orderDir';
                }
                $finalLine .= ');';
                $this->file_lines[] = $finalLine;
                $this->file_lines[] = [1];
                $this->file_lines[] = '}';
            }
            if ($includeListLoaders == true) {
                $this->file_lines[] = "/**";
                $this->file_lines[] = '* loadDataFrom' . ucfirst($row_two["COLUMN_NAME"]) . 's';
                $this->file_lines[] = '* @return mixed[] [status =>  bool, count => integer, message =>  string]';
                $this->file_lines[] = "*/";
                $this->file_lines[] = 'public function loadFrom' .
                ucfirst($row_two["COLUMN_NAME"]) . 's(array $values): array';
                $this->file_lines[] = '{';
                    $this->file_lines[] = [2];
                    $this->file_lines[] = 'return $this->loadIndexs("' . $row_two["COLUMN_NAME"] . '", $values);';
                $this->file_lines[] = [1];
                $this->file_lines[] = '}';
            }
        }
    }

    protected function createModelGetters(string $target_table, array $data_two): void
    {
        $this->file_lines[] = "// Getters";
        foreach ($data_two as $row_two) {
            if ($row_two["COLUMN_NAME"] != "id") {
                $return_type_addon = "";
                $use_type = $this->getColType(
                    $row_two["DATA_TYPE"],
                    $row_two["COLUMN_TYPE"],
                    $target_table,
                    $row_two["COLUMN_NAME"]
                );
                if ($use_type == "str") {
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
        $this->file_lines[] = "// Data Design";
        $this->file_lines[] = 'protected $fields = [';
        $this->file_lines[] = [2];
        foreach ($data_two as $row_two) {
            $this->file_lines[] = '"' . $row_two["COLUMN_NAME"] . '",';
        }
        $this->file_lines[] = [1];
        $this->file_lines[] = '];';
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
            if ($use_type == "str") {
                $detected_default = str_replace("'", "", $detected_default);
                if ((strlen($detected_default) > 0) && ($detected_default !== "null")) {
                    if (strpos($detected_default, '"') === false) {
                        $detected_default = '"' . $detected_default . '"';
                    }
                }
                if ($detected_default == "") {
                    $detected_default = "\'\'";
                }
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
        global $GEN_NAMESPACE_SINGLE, $GEN_ADD_DB_TO_TABLE;
        $add_target_db_to_class = "";
        if ($GEN_ADD_DB_TO_TABLE == true) {
            $add_target_db_to_class = $database . ".";
        }
        $this->file_lines[] = '<?php';
        $this->file_lines[] = '';
        $this->file_lines[] = 'namespace ' . $GEN_NAMESPACE_SINGLE . ';';
        $this->file_lines[] = '';
        $this->file_lines[] = 'use YAPF\DbObjects\GenClass\GenClass as GenClass;';
        $this->file_lines[] = '';
        $this->file_lines[] = '// Do not edit this file, rerun gen.php to update!';
        $this->file_lines[] = 'class ' . $class_name . ' extends genClass';
        $this->file_lines[] = '{';
        $this->file_lines[] = [1];
        $this->file_lines[] = 'protected $use_table = "' . $add_target_db_to_class . '' . $target_table . '";';
    }
}
