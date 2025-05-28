<?php

namespace YAPF\Framework\Generator;

use YAPF\Framework\Generator\FileWriter;

abstract class Shared extends FileWriter
{
    public function __construct()
    {
        global $system;
        $this->sql = $system->getSQL();
    }
    protected string $filename = "";
    protected string $namespaceSingle = "";
    protected string $namespaceSet = "";
    protected string $className = "";
    protected string $workingTable = "";
    protected array $cols = [];
    /**
     * getTableColumns
     * returns the table schema.columns or null
     * @return mixed[] or null
     */
    protected function getTableColumns(string $target_database, string $target_table): ?array
    {
        $whereConfig = [
            "fields" => ["TABLE_SCHEMA", "TABLE_NAME"],
            "matches" => ["=", "="],
            "values" => [$target_database, $target_table],
            "types" => ["s", "s"],
        ];
        $basic_config = [
            "table" => "information_schema.columns",
            "fields" => ["COLUMN_NAME", "COLUMN_DEFAULT", "DATA_TYPE", "COLUMN_TYPE"],
        ];
        $results = $this->sql->selectV2($basic_config, null, $whereConfig);
        $returndata = null;
        if ($results->status == true) {
            $returndata = $results->dataset;
        }
        return $returndata;
    }

    protected const STRING_TYPES = [
        "varchar",
        "text",
        "char",
        "longtext",
        "mediumtext",
        "tinytext",
        "date",
        "datetime",
    ];
    protected const INT_TYPES = ["tinyint", "int", "smallint", "bigint", "mediumint", "enum", "timestamp"];
    protected const FLOAT_TYPES = ["decimal", "float", "double"];
    protected const KNOWN_TYPES = [
        "varchar",
        "text",
        "char",
        "longtext",
        "mediumtext",
        "tinytext",
        "date",
        "datetime",
        "tinyint",
        "int",
        "smallint",
        "bigint",
        "mediumint",
        "enum",
        "timestamp",
        "decimal",
        "float",
        "double",
    ];

    /**
     * getColType
     * returns the col type for the selected target_table
     */
    protected function getColType(
        string $targetType,
        string $columnType
    ): string {
        if (in_array($targetType, self::KNOWN_TYPES) == false) {
            return "str";
        }
        if (in_array($targetType, self::INT_TYPES)) {
            if (strpos($columnType, 'tinyint(1)') !== false) {
                return "bool";
            }
            return "int";
        }
        if (in_array($targetType, self::FLOAT_TYPES)) {
            return "float";
        }
        return "str";
    }
}
