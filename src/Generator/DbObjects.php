<?php

namespace YAPF\Framework\Generator;

use YAPF\Framework\Core\SQLi\SqlConnectedClass;
use YAPF\Framework\Responses\MySQLi\SelectReply;

class DbObjects extends SqlConnectedClass
{
    protected array $databaseTables = [];
    protected array $databaseLinks = [];
    public function __construct(
        protected array $databases,
        protected string $namespace = "App/Models/<!DBName!>(Set)",
        protected string $saveToFolder = "src/Models/<!DBName!>/(Set)",
        protected array $ignoreTables = [],
        protected bool $prefixDbName = false
    ) {
        parent::__construct();
        $this->info = ["files" => 0, "lines" => 0, "error" => true];
        $this->readyFolders();
        $this->loadTables();
        $this->createObjects();
    }
    protected function readyFolders(): void
    {
        foreach ($this->databases as $dbName) {
            $worker = str_replace("<!DBName!>", $dbName, $this->saveToFolder);
            $worker = str_replace("(Set)", "", $worker);
            $this->delTree($worker);
            mkdir($worker);
            $worker = str_replace("<!DBName!>", $dbName, $this->saveToFolder);
            $worker = str_replace("(Set)", "Set/", $worker);
            mkdir($worker);
        }
    }
    protected function createObjects(): void
    {
        foreach ($this->databases as $dbName) {
            $singleFactory = new SingleObjectFactory(
                $dbName,
                $this->databaseTables,
                $this->databaseLinks[$dbName],
                $this->prefixDbName,
                $this->namespace,
                $this->saveToFolder,
                $this->ignoreTables
            );
            $setFactory = new SetObjectFactory(
                $dbName,
                $this->databaseTables,
                $this->databaseLinks[$dbName],
                $this->prefixDbName,
                $this->namespace,
                $this->saveToFolder,
                $this->ignoreTables
            );
            $stats = $singleFactory->statsWrite();
            $merge = $setFactory->statsWrite();
            $stats["files"] += $merge["files"];
            $stats["lines"] += $merge["lines"];
            $stats["log"] .= " | " . $merge["log"];
            if ($merge["error"] != $stats["error"]) {
                $stats["error"] = true;
            }
            $this->info["files"] = $stats["files"];
            $this->info["lines"] = $stats["lines"];
            $this->info["error"] = $stats["error"];
            $this->log .= "Write log: " . $stats["log"];
            unset($merge);
        }
    }
    protected array $info = [];
    /**
     * getStats
     * gets the stats
     * @return mixed[] "files"=>0,"lines"=>0,"error"=>false
     */
    public function getStats(): array
    {
        return $this->info;
    }
    protected string $log = "";
    public function getLog(): string
    {
        return $this->log;
    }
    protected function loadTables(): void
    {
        foreach ($this->databases as $dbName) {
            $whereConfig = [
                "fields" => ["TABLE_SCHEMA"],
                "matches" => ["="],
                "values" => [$dbName],
                "types" => ["s"],
            ];
            $basic_config = [
                "table" => "information_schema.TABLES",
                "fields" => ["TABLE_NAME"],
            ];
            $this->databaseTables[$dbName] = [];
            $this->databaseLinks[$dbName] = [];
            $results = $this->sql->selectV2($basic_config, null, $whereConfig);
            foreach ($results->dataset as $entry) {
                $this->databaseTables[$dbName][] = $entry;
            }
            $this->databaseLinks[$dbName] = $this->getLinks($dbName);
        }
    }
    protected function delTree($dir): bool
    {
        if (is_dir($dir) == false) {
            return true;
        }
        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
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
    protected function getLinks(string $targetDatabase): array
    {
        $packet = [];
        foreach ($this->getDBForeignKeys($targetDatabase)->dataset as $entry) {
            $idme = strtolower($entry["TABLE_NAME"] . $entry["COLUMN_NAME"]);
            if (array_key_exists($idme, $packet) == true) {
                continue;
            }
            $packet[] = [
                "sourceTable" => $entry["TABLE_NAME"],
                "sourceField" => $entry["COLUMN_NAME"],
                "targetTable" => $entry["REFERENCED_TABLE_NAME"],
                "targetField" => $entry["REFERENCED_COLUMN_NAME"],
            ];
        }
        return $packet;
    }
}
