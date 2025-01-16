<?php

namespace YAPF\Framework\DbObjects\CollectionSet;

use YAPF\Framework\Core\SQLi\SqlConnectedClass;
use YAPF\Framework\DbObjects\GenClass\GenClass;
use YAPF\Framework\Responses\DbObjects\GroupedCountReply;
use YAPF\Framework\Responses\MySQLi\CountReply;

abstract class CollectionSetCore extends SqlConnectedClass
{
    protected array $collected = [];
    protected $indexes = [];
    protected ?string $workerClass = null;
    protected ?GenClass $worker = null;

    protected bool $disableUpdates = false;
    protected ?array $limitedFields = null;

    /**
     * __construct
     * sets up the worker class
     * by taking the assigned collection name
     * example: TreeCollectionSet
     * removing: CollectionSet
     * to get: Tree as the base class for this collection
     */
    public function __construct(string $workerClass)
    {
        global $system;
        $this->workerClass = $workerClass;
        parent::__construct();
    }

    /**
     * getTable
     * returns the table assigned to the worker
     */
    public function getTable(): string
    {
        $this->makeWorker();
        return $this->worker->getTable();
    }

    /**
     * getCount
     * returns the number of objects in this collection set
     */
    public function getCount(): int
    {
        return count($this->collected);
    }

    /**
     * getAllIds
     * alias of uniqueArray
     * defaulted to id or use_id_field
     * @return mixed[] [value,...]
     */
    public function getAllIds(): array
    {
        $this->makeWorker();
        return $this->uniqueArray("id");
    }

    /**
     * uniqueArray
     * gets a Unique array of values based on fieldName from
     * the objects.
     * @return array<mixed>
     */
    protected function uniqueArray(string $fieldName): array
    {
        $found_values = [];
        $getFunction = "get" . ucfirst($fieldName);
        foreach ($this->collected as $object) {
            $value = $object->$getFunction();
            if (in_array(needle: $value, haystack: $found_values) == false) {
                $found_values[] = $value;
            }
        }
        return $found_values;
    }
    /**
     * > notes: minCountToShow applys the filter
     * after counting everything so setting to not null
     * is worse than leaving it as null!
     * unless you want SQL to trim the result stack
     * before passing it to php
     */
    public function groupCountInDb(string $groupByField, ?int $minCountToShow = null): GroupedCountReply
    {

        $this->makeWorker();
        if ($this->worker->hasField($groupByField) == false) {
            return new GroupedCountReply("Unknown groupby field");
        }
        $having = "";
        if ($minCountToShow != null) {
            if ($minCountToShow < 1) {
                $minCountToShow = 1;
                $this->addError(errorMessage: "Min count to show was to low set to 1");
            }
            $having = " HAVING SortedCount >= " . $minCountToShow . "";
        }
        $sqlRaw = '' .
            'SELECT count(id) as SortedCount, ' . $groupByField . '' .
            ' FROM ' . $this->worker->getTable() . '' .
            ' GROUP BY ' . $groupByField . '' .
            $having .
            ' ORDER BY SortedCount DESC';
        $reply = $this->sql->directSelectSQL($sqlRaw);
        if ($reply->status == false) {
            $this->addError(errorMessage: $reply->message);
            return new GroupedCountReply(message: $reply->message);
        }
        $results = [];
        foreach ($reply->dataset as $entry) {
            $results[$entry[$groupByField]] = $entry["SortedCount"];
        }
        return new GroupedCountReply(message: "ok", results: $results, status: true);
    }

    /**
     * countInDB
     * $whereConfig: see selectV2.readme
     * Requires a id field
     */
    public function countInDB(?array $whereConfig = null): CountReply
    {
        $this->makeWorker();
        $loadWhereConfig = $this->worker->autoFillWhereConfig($whereConfig);
        if ($loadWhereConfig->status == false) {
            return new CountReply(message: $loadWhereConfig->message);
        }
        $whereConfig = $loadWhereConfig->data;
        $reply = $this->sql->basicCountV2(table: $this->worker->getTable(), whereConfig: $whereConfig);
        if ($reply->status == false) {
            $this->addError(errorMessage: $reply->message);
            return new CountReply(message: $reply->message);
        }
        return $reply;
    }

    public function limitFields(array $fields): void
    {
        $this->makeWorker();
        if (in_array(needle: "id", haystack: $fields) == false) {
            $fields = array_merge(["id"], $fields);
        }
        $this->limitedFields = $fields;
        $this->disableUpdates = true;
    }
    public function getUpdatesStatus(): bool
    {
        return $this->disableUpdates;
    }

    protected function rebuildIndex(): void
    {
        $this->indexes = array_keys($this->collected);
    }

    /**
     * makeWorker
     * creates the worker object for the collection set
     * if one has not already been created.
     */
    protected function makeWorker(): ?GenClass
    {
        if ($this->worker == null) {
            $this->worker = new $this->workerClass();
        }
        return $this->worker;
    }
    /**
     * addToCollected
     * adds an object to the collected array
     * using its id as the index.
     */
    public function addToCollected($object): void
    {
        $this->collected[$object->getId()] = $object;
        $this->rebuildIndex();
    }

    protected $fastObjectArrayIndex = [];
    protected $fastIndexDataset = [];
    /**
     * buildObjectGetIndex
     * processes the collected objects and builds a fast index
     * to use when fetching by not the ID.
     * Note: if 2 objects share a value on a field the last checked one will take
     * the spot.
     * example:
     * A = 5
     * B = 3
     * C = 5
     * object with index 5 would return C and not A
     * for objects with the bad_id flag
     * the object is stored and not the ID
     */
    protected function buildObjectGetIndex(string $fieldName, bool $force_rebuild = false): void
    {
        $this->makeWorker();
        if ((in_array(needle: $fieldName, haystack: $this->fastObjectArrayIndex) == false) || ($force_rebuild == true)) {
            $loadString = "get" . ucfirst($fieldName);
            if (method_exists($this->worker, $loadString)) {
                $this->fastObjectArrayIndex[] = $fieldName;
                $index = [];
                foreach ($this->collected as $object) {
                    $indexValue = $object->$loadString();
                    if ($indexValue === true) {
                        $indexValue = 1;
                    } elseif ($indexValue == false) {
                        $indexValue = 0;
                    }
                    if (array_key_exists(key: $indexValue, array: $index) == false) {
                        $index[$indexValue] = [];
                    }
                    $index[$indexValue][] = $object->getId();
                }
                $this->fastIndexDataset[$fieldName] = $index;
            }
        }
    }
    /**
     * indexSearch
     * returns an array of objects that matched the search settings
     * @return mixed[] [object,...]
     */
    protected function indexSearch(string $fieldName, $fieldValue): array
    {
        $this->makeWorker();
        $this->buildObjectGetIndex(fieldName: $fieldName);
        $return_objects = [];
        if (array_key_exists(key: $fieldName, array: $this->fastIndexDataset) == false) {
            $this->addError(errorMessage: "Field was not found as part of search array dataset");
            return [];
        }
        $loadString = "get" . ucfirst($fieldName);
        if (method_exists($this->worker, $loadString) == false) {
            $this->addError(errorMessage: "get function is not supported");
            return [];
        }
        if (array_key_exists(key: $fieldValue, array: $this->fastIndexDataset[$fieldName]) == false) {
            $this->addError(errorMessage: "value does not match dataset search");
            return [];
        }
        $return_objects = [];
        foreach ($this->fastIndexDataset[$fieldName][$fieldValue] as $objectid) {
            if (array_key_exists(key: $objectid, array: $this->collected) == true) {
                $return_objects[] = $this->collected[$objectid];
            }
        }
        return $return_objects;
    }
}
