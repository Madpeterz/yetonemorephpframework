<?php

namespace YAPF\Framework\Generator;

abstract class ModelFactoryShared
{
    protected const STRING_TYPES = ["varchar","text","char","longtext","mediumtext","tinytext","date","datetime"];
    protected const INT_TYPES = ["tinyint", "int","smallint","bigint","mediumint","enum","timestamp"];
    protected const FLOAT_TYPES = ["decimal","float","double"];
    protected const KNOWN_TYPES = [
        "varchar","text","char","longtext","mediumtext","tinytext","date","datetime",
        "tinyint", "int","smallint","bigint","mediumint","enum","timestamp",
        "decimal","float","double",
    ];

    protected array $fileLines = [];
    protected string $className = "";
    protected string $namespaceSingle = "";
    protected string $namespaceSet = "";
    protected string $database = "";
    protected string $table = "";
    protected array $cols = [];
    protected array $links = [];
    protected bool $addDbToTable = false;
    protected bool $use_output = true;
    protected bool $console_output = false;
    protected string $output = "";

    public function __construct(
        string $className,
        string $namespaceSingle,
        string $namespaceSet,
        string $database,
        string $table,
        array $cols,
        array $relatedLinks,
        bool $addDbToTable
    ) {
        $this->className = $className;
        $this->namespaceSingle = $namespaceSingle;
        $this->namespaceSet = $namespaceSet;
        $this->database = $database;
        $this->table = $table;
        $this->cols = $cols;
        $this->links = $relatedLinks;
        $this->addDbToTable = $addDbToTable;
        $this->createNow();
    }

    protected function writeOutput(string $message): void
    {
        if ($this->use_output == false) {
            return;
        }
        if ($this->console_output == true) {
            echo $message . "\n";
            return;
        }
        $this->output .= $message;
        $this->output .= "<br/>";
    }

    public function createNow(): void
    {
        $this->createModelHeader();
        $this->createModelDataset();
        $this->createModelSetters();
        $this->createModelGetters();
        $this->createModelLoaders();
        $this->createRelatedLoaders();
        $this->createModelFooter();
    }

    protected function createModelFooter(): void
    {
    }
    protected function createRelatedLoaders(): void
    {
    }
    protected function createModelGetters(): void
    {
    }
    protected function createModelSetters(): void
    {
    }
    protected function createModelDataset(): void
    {
    }
    protected function createModelHeader(): void
    {
    }
    protected function createModelLoaders(): void
    {
    }

    /**
     * getLines
     * @return array<string>
     */
    public function getLines(): array
    {
        return $this->fileLines;
    }

   /**
     * getColType
     * returns the col type for the selected target_table
     */
    protected function getColType(
        string $targetType,
        string $columnType,
        string $table,
        string $columnName
    ): string {
        if (in_array($targetType, self::KNOWN_TYPES) == false) {
            $error_msg = "Table: " . $table . " Column: " . $columnName . " unknown type: ";
            $error_msg .= $targetType . " defaulting to string!<br/>";
            $this->writeOutput("Error: " . $error_msg);
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
