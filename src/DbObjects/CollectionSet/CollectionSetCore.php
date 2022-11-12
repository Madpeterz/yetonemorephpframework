<?php

namespace YAPF\Framework\DbObjects\CollectionSet;

use YAPF\Framework\Core\SQLi\SqlConnectedClass;
use YAPF\Framework\DbObjects\GenClass\GenClass;

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
        $this->cache = $system->getCacheDriver();
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
            if (in_array($value, $found_values) == false) {
                $found_values[] = $value;
            }
        }
        return $found_values;
    }

    /**
     * countInDB
     * $whereConfig: see selectV2.readme
     * Requires a id field
     * @return ?int  returns the count or null if failed
     */
    public function countInDB(?array $whereConfig = null): ?int
    {
        $this->makeWorker();
        $whereConfig = $this->worker->autoFillWhereConfig($whereConfig);
        // Cache support
        $hitCache = false;
        $currentHash = "";
        if ($this->cache != null) {
            $currentHash = $this->cache->getHash(
                $whereConfig,
                ["countDB" => "yep"],
                ["countDB" => "yep"],
                ["countDB" => "yep"],
                $this->worker->getTable(),
                count($this->worker->getFields()),
                false
            );
            $hitCache = $this->cache->cacheValid($this->worker->getTable(), $currentHash, false);
            if ($hitCache == true) {
                $reply = $this->cache->readHash($this->worker->getTable(), $currentHash);
                if (is_array($reply) == true) {
                    return $reply["count"];
                }
            }
        }

        $reply = $this->sql->basicCountV2($this->worker->getTable(), $whereConfig);
        if ($reply->status == false) {
            $this->addError($reply["message"]);
            return null;
        }
        if (($this->cache != null) && ($reply->status == true)) {
            // push data to cache so we can avoid reading from DB as much
            $this->cache->writeHash($this->worker->getTable(), $currentHash, ["count" => $reply->items], false);
        }
        return $reply->items;
    }

    public function limitFields(array $fields): void
    {
        $this->makeWorker();
        if (in_array("id", $fields) == false) {
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

    public function setCacheAllowChanged(bool $status = true): void
    {
        $this->cacheAllowChanged = $status;
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
        if ((in_array($fieldName, $this->fastObjectArrayIndex) == false) || ($force_rebuild == true)) {
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
                    if (array_key_exists($indexValue, $index) == false) {
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
        $this->buildObjectGetIndex($fieldName);
        $return_objects = [];
        if (array_key_exists($fieldName, $this->fastIndexDataset) == false) {
            $this->addError("Field was not found as part of search array dataset");
            return [];
        }
        $loadString = "get" . ucfirst($fieldName);
        if (method_exists($this->worker, $loadString) == false) {
            $this->addError("get function is not supported");
            return [];
        }
        if (array_key_exists($fieldValue, $this->fastIndexDataset[$fieldName]) == false) {
            $this->addError("value does not match dataset search");
            return [];
        }
        $return_objects = [];
        foreach ($this->fastIndexDataset[$fieldName][$fieldValue] as $objectid) {
            if (array_key_exists($objectid, $this->collected) == true) {
                $return_objects[] = $this->collected[$objectid];
            }
        }
        return $return_objects;
    }
}
