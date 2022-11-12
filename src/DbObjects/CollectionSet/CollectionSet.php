<?php

namespace YAPF\Framework\DbObjects\CollectionSet;

use YAPF\Framework\Responses\DbObjects\SetsLoadReply;
use YAPF\Framework\Responses\MySQLi\SelectReply;
use Iterator;
use YAPF\Framework\DbObjects\GenClass\GenClass;

abstract class CollectionSet extends CollectionSetBulk implements Iterator
{
    protected $position = 0;
    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): GenClass
    {
        return $this->collected[$this->indexes[$this->position]];
    }

    public function key(): int
    {
        return $this->indexes[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        if ($this->position < 0) {
            return false;
        }
        if (array_key_exists($this->position, $this->indexes) == false) {
            return false;
        }
        $index = $this->indexes[$this->position];
        if (array_key_exists($index, $this->collected) == false) {
            return false;
        }
        return true;
    }

    /**
     * getCollection
     * please use: getAllIds and getObjectByID
     * as this method duplicates objects increasing memory usage
     * returns an array of objects in this collection
     * @return object[] [object,...]
     */
    public function getCollection(): array
    {
        return array_values($this->collected);
    }

    /**
     * getLinkedArray
     * returns a key value pair array for all objects in collection
     * example id & name
     * @return mixed[] [left field Value => right field value,...]
     */
    public function getLinkedArray(string $leftField, string $RightField): array
    {
        $keyFieldGetter = "get" . ucfirst($leftField);
        $ValueFieldGetter = "get" . ucfirst($RightField);
        $worker = new $this->workerClass();
        if (method_exists($worker, $keyFieldGetter) == false) {
            $this->addError("Field: " . $leftField . " is missing");
            return [];
        }
        if (method_exists($worker, $ValueFieldGetter) == false) {
            $this->addError("Field: " . $RightField . " is missing");
            return [];
        }
        $return_array = [];
        foreach ($this->collected as $object) {
            $return_array[$object->$keyFieldGetter()] = $object->$ValueFieldGetter();
        }
        return $return_array;
    }

    /**
     * getIdsMatchingField
     * returns an array of the ids of objects that match the field and value
     * uses the: built_search_index_level_1 to speed up repeated calls.
     * with the same field.
     * @return integer[] [id,...]
     */
    public function getIdsMatchingField(string $fieldName, $fieldValue): array
    {
        $objects = $this->indexSearch($fieldName, $fieldValue);
        $ids = [];
        foreach ($objects as $object) {
            $ids[] = $object->getId();
        }
        return $ids;
    }
    /**
     * getFirst
     * returns the first object found in the collection
     * if none are found it returns null
     * @return object or null, object will be of the worker type of the set.
     */
    public function getFirst(): ?object
    {
        foreach ($this->collected as $value) {
            return $value;
        }
        return null;
    }
    /**
     * getWorkerClass
     * returns the class name assigned object for this collection
     */
    public function getWorkerClass(): string
    {
        return $this->workerClass;
    }
    /**
     * getCollectionHash
     * returns a sha256 hash of the full collection
     */
    public function getCollectionHash(): string
    {
        $hash_builder = "";
        foreach ($this->collected as $entry) {
            $hash_builder .= $entry->fieldsHash();
        }
        return hash("sha256", $hash_builder);
    }
    /**
     * getObjectByField
     * Note: Please use getObjectByID if your using the id field
     * as its faster and does not need a index!
     * search the index for a object that matches
     * fieldname to value, if a object shares
     * a value the last loaded one is taken.
     */
    public function getObjectByField(string $fieldName, $value): ?object
    {
        return $this->findObjectByField($fieldName, $value, false);
    }
    /**
     * getObjectByField
     * search the index for a object that matches
     * fieldname to value, if a object shares
     * a value the last entry is used
     */
    protected function findObjectByField(string $fieldName, $value): ?object
    {
        $objects = $this->indexSearch($fieldName, $value);
        if (count($objects) >= 1) {
            return array_pop($objects);
        }
        return null;
    }
    /**
     * getObjectByID
     * returns a object that matches the selected id
     * returns null if not found
     */
    public function getObjectByID(int $idNumber): ?object
    {
        $this->makeWorker();
        if (array_key_exists($idNumber, $this->collected) == true) {
            return $this->collected[$idNumber];
        }
        return null;
    }
    /**
     * getAllByField
     * alias of uniqueArray
     * @return mixed[] [value,...]
     */
    public function getAllByField(string $fieldName): array
    {
        return $this->uniqueArray($fieldName);
    }


    /**
     * This function takes an array of fields to ignore and an optional boolean to invert the ignore list.
     * It then loops through the collected entries and returns an array of the entries mapped to an array
     * of the fields to ignore
     *
     * @param array ignoreFields an array of fields to ignore when converting the object to an array.
     * @param bool invertIgnore If true, the ignoreFields will be inverted.
     * @return mixed[] [id => array of mapped object,...]
     */
    public function getCollectionToMappedArray(array $ignoreFields = [], bool $invertIgnore = false): array
    {
        $results = [];
        foreach ($this->collected as $entry) {
            /** @var GenClass $entry */
            $results[$entry->getId()] = $entry->objectToMappedArray($ignoreFields, $invertIgnore);
        }
        return $results;
    }

    /**
     * loadMatching
     * fields = keys from input
     * values = values from input
     * Please use loadWithConfig when you can :P
     */
    public function loadMatching(array $input): SetsLoadReply
    {
        $whereConfig = [
            "fields" => array_keys($input),
            "values" => array_values($input),
        ];
        return $this->loadWithConfig($whereConfig);
    }

    /**
     * loadOnField
     * uses one field to load from the database with
     * for full control please use the method loadWithConfig
     */
    protected function loadOnField(
        string $field,
        $value,
        int $limit = 0,
        string $orderBy = "id",
        string $orderDirection = "DESC"
    ): SetsLoadReply {
        if (is_object($value) == true) {
            $errormsg = "Attempted to pass value as a object!";
            $this->addError($errormsg);
            return ["status" => false,"message" => "Attempted to pass a value as a object!"];
        }
        $whereConfig = [
            "fields" => [$field],
            "values" => [$value],
        ];
        $orderConfig = ["enabled" => true,"byField" => $orderBy,"dir" => $orderDirection];
        $optionsConfig = ["pageNumber" => 0,"limit" => $limit];
        return $this->loadWithConfig($whereConfig, $orderConfig, $optionsConfig);
    }
    /**
     * loadLimited
     * alias of loadNewest
     * paged loading support with limiters
     * for full control please use the method loadWithConfig
     */
    public function loadLimited(
        int $limit = 12,
        int $page = 0,
        string $orderBy = "id",
        string $orderDirection = "ASC",
        ?array $whereConfig = null
    ): SetsLoadReply {
        return $this->loadNewest($limit, $page, $orderBy, $orderDirection, $whereConfig);
    }
    /**
     * loadNewest
     * default setup is to order by id newest first.
     * for full control please use the method loadWithConfig
     */
    public function loadNewest(
        int $limit = 12,
        int $page = 0,
        string $orderBy = "id",
        string $orderDirection = "DESC",
        ?array $whereConfig = null
    ): SetsLoadReply {
        return $this->loadWithConfig(
            $whereConfig,
            ["enabled" => true,"byField" => $orderBy,"dir" => $orderDirection],
            ["pageNumber" => $page,"limit" => $limit]
        );
    }
    /**
     * loadAll
     * Loads everything it can get its hands
     * ordered by id ASC by default
     * for full control please use the method loadWithConfig
     */
    public function loadAll(string $orderBy = "id", string $orderDirection = "ASC"): SetsLoadReply
    {
        return $this->loadWithConfig(
            null,
            ["enabled" => true,"byField" => $orderBy,"dir" => $orderDirection]
        );
    }


    /**
     * loadWithConfig
     * Uses the select V2 system to load data
     * its magic!
     * see the v2 readme
     */
    public function loadWithConfig(
        ?array $whereConfig = null,
        ?array $order_config = null,
        ?array $options_config = null,
        ?array $joinTables = null
    ): SetsLoadReply {
        $this->makeWorker();
        $basic_config = ["table" => $this->worker->getTable()];
        if ($this->disableUpdates == true) {
            $basic_config["fields"] = $this->limitedFields;
        }
        $whereConfig = $this->worker->autoFillWhereConfig($whereConfig);
        // Cache support
        $hitCache = false;
        $currentHash = "";
        if ($this->cache != null) {
            $mergedData = $basic_config;
            if (is_array($joinTables) == true) {
                $mergedData = array_merge($basic_config, $joinTables);
            }
            $currentHash = $this->cache->getHash(
                $this->getTable(),
                count($this->worker->getFields()),
                false,
                $whereConfig,
                $order_config,
                $options_config,
                $mergedData
            );
            $hitCache = $this->cache->cacheValid($this->getTable(), $currentHash, false);
        }
        if ($hitCache == true) {
            // Valid data from cache!
            $loadResult = $this->cache->readHash($this->getTable(), $currentHash);
            if (is_array($loadResult) == true) {
                return $this->processLoad(new SelectReply("from cache", true, $loadResult));
            }
        }
        // Cache missed, read from the DB
        $loadData = $this->sql->selectV2(
            $basic_config,
            $order_config,
            $whereConfig,
            $options_config,
            $joinTables
        );
        if ($loadData->status == false) {
            $this->addError("Unable to load data: " . $loadData->message);
            return new SetsLoadReply($this->myLastErrorBasic);
        }
        if ($this->cache != null) {
            // push data to cache so we can avoid reading from DB as much
            $this->cache->writeHash(
                $this->worker->getTable(),
                $currentHash,
                $loadData->dataset,
                $this->cacheAllowChanged
            );
        }
        return $this->processLoad($loadData);
    }

    /**
     * loadIndexes
     * returns where fieldname value for the row is IN $values
     */
    protected function loadIndexes(string $fieldName = "id", array $values = []): SetsLoadReply
    {
        $this->makeWorker();
        $uids = [];
        foreach ($values as $id) {
            if (in_array($id, $uids) == false) {
                $uids[] = $id;
            }
        }
        if (count($uids) == 0) {
            $this->addError("No ids sent!");
            return new SetsLoadReply($this->myLastErrorBasic);
        }
        $typeCheck = $this->worker->getFieldType($fieldName, true);
        if ($typeCheck == null) {
            $this->addError("Invalid field: " . $fieldName);
            return new SetsLoadReply($this->myLastErrorBasic);
        }
        return $this->loadWithConfig([
            "fields" => [$fieldName],
            "matches" => ["IN"],
            "values" => [$uids],
            "types" => [$typeCheck],
        ]);
    }
    /**
     * processLoad
     * takes the reply from mysqli and fills out objects and builds the collection
     * @return mixed[] [status =>  bool, count => integer, message =>  string]
     */
    protected function processLoad(SelectReply $loadData): SetsLoadReply
    {
        if ($loadData->status == false) {
            $this->addError("loadData status is false");
            return new SetsLoadReply($this->myLastErrorBasic);
        }
        $this->makeWorker();
        $oldCount = $this->getCount();
        foreach ($loadData->dataset as $entry) {
            $new_object = new $this->workerClass($entry);
            if ($this->disableUpdates == true) {
                $new_object->noUpdates();
            }
            if ($new_object->isLoaded() == true) {
                $this->collected[$entry["id"]] = $new_object;
            }
        }
        $this->rebuildIndex();
        return new SetsLoadReply("ok", true, $this->getCount() - $oldCount);
    }
}
