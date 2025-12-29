<?php

namespace YAPF\Framework\DbObjects\CollectionSet;

use YAPF\Framework\Responses\DbObjects\SetsLoadReply;
use YAPF\Framework\Responses\MySQLi\SelectReply;
use YAPF\Framework\DbObjects\GenClass\GenClass;

abstract class CollectionSetFunctions extends CollectionSetBulk
{
    /**
     * > This function returns an array of object ids from the collection that match the given
     * field to the field value
     * @return false|int[] An array of object ids.
     */
    public function getObjectIdsByField(string $field, $fieldValue): false|array
    {
        $getter = "get" . ucFirst($field);
        if ($this->worker->hasField(fieldName: $field) == false) {
            $this->addError(errorMessage: "Unknown fieldname: " . $field);
            return false;
        }
        $reply = [];
        foreach ($this->collected as $item) {
            $check = $item->$getter();
            if ($check == $fieldValue) {
                $reply[] = $item->getId();
            }
        }
        return $reply;
    }

    public function getWithFieldValue(string $field, string $fieldvalue): false|array
    {
        $getter = "get" . ucFirst($field);
        if ($this->worker->hasField(fieldName: $field) == false) {
            $this->addError(errorMessage: "Unknown fieldname: " . $field);
            return false;
        }
        if ($this->worker->getFieldType(fieldName: $field, as_mysqli_code: true) != "s") {
            $this->addError(errorMessage: "this function is for string partial matchs only");
            return false;
        }
        $reply = [];
        foreach ($this->collected as $item) {
            $check = $item->$getter();
            if (str_contains(haystack: $check, needle: $fieldvalue) == true) {
                $reply[] = $item->getId();
            }
        }
        return $reply;
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
        $keyFieldGetter = "_" . ucfirst($leftField);
        $ValueFieldGetter = "_" . ucfirst($RightField);
        $worker = new $this->workerClass();
        if ($worker->getFieldType($leftField) == null) {
            $this->addError(errorMessage: "Field: " . $leftField . " is missing");
            return [];
        } elseif ($worker->getFieldType($RightField) == null) {
            $this->addError(errorMessage: "Field: " . $RightField . " is missing");
            return [];
        }
        $return_array = [];
        foreach ($this->collected as $object) {
            $return_array[$object->$keyFieldGetter] = $object->$ValueFieldGetter;
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
        $objects = $this->indexSearch(fieldName: $fieldName, fieldValue: $fieldValue);
        $ids = [];
        foreach ($objects as $object) {
            $ids[] = $object->_Id;
        }
        return $ids;
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
        return hash(algo: "sha256", data: $hash_builder);
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
        return $this->findObjectByField(fieldName: $fieldName, value: $value);
    }
    /**
     * getObjectByField
     * search the index for a object that matches
     * fieldname to value, if a object shares
     * a value the last entry is used
     */
    protected function findObjectByField(string $fieldName, $value): ?object
    {
        $objects = $this->indexSearch(fieldName: $fieldName, fieldValue: $value);
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
    public function getObjectByID(?int $idNumber): ?object
    {
        if ($idNumber === null) {
            return null;
        }
        $this->makeWorker();
        if (array_key_exists(key: $idNumber, array: $this->collected) == true) {
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
        return $this->uniqueArray(fieldName: $fieldName);
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
            $results[$entry->_Id] = $entry->objectToMappedArray(
                ignoreFields: $ignoreFields,
                invertIgnore: $invertIgnore
            );
        }
        return $results;
    }

    /**
     * loadMatching
     * fields = keys from input
     * values = values from input
     * Please use loadWithConfig when you can :P
     */
    public function loadMatching(array $input, ?array $limitFields = null): SetsLoadReply
    {
        $whereConfig = [
            "fields" => array_keys($input),
            "values" => array_values($input),
        ];
        return $this->loadWithConfig(whereConfig: $whereConfig, limitFields: $limitFields);
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
        string $orderDirection = "DESC",
        ?array $limitFields = null
    ): SetsLoadReply {
        if (is_object($value) == true) {
            $errormsg = "Attempted to pass value as a object!";
            $this->addError(errorMessage: $errormsg);
            return new SetsLoadReply(message: "Attempted to pass a value as a object!", status: false);
        }
        $whereConfig = [
            "fields" => [$field],
            "values" => [$value],
        ];
        $orderConfig = ["enabled" => true, "byField" => $orderBy, "dir" => $orderDirection];
        $optionsConfig = ["pageNumber" => 0, "limit" => $limit];
        return $this->loadWithConfig(
            whereConfig: $whereConfig,
            orderConfig: $orderConfig,
            optionsConfig: $optionsConfig,
            limitFields: $limitFields
        );
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
        ?array $whereConfig = null,
        ?array $limitFields = null
    ): SetsLoadReply {
        return $this->loadNewest(
            limit: $limit,
            page: $page,
            orderBy: $orderBy,
            orderDirection: $orderDirection,
            whereConfig: $whereConfig,
            limitFields: $limitFields
        );
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
        ?array $whereConfig = null,
        ?array $limitFields = null
    ): SetsLoadReply {
        return $this->loadWithConfig(
            whereConfig: $whereConfig,
            orderConfig: ["enabled" => true, "byField" => $orderBy, "dir" => $orderDirection],
            optionsConfig: ["pageNumber" => $page, "limit" => $limit],
            limitFields: $limitFields
        );
    }
    /**
     * loadAll
     * Loads everything it can get its hands
     * ordered by id ASC by default
     * for full control please use the method loadWithConfig
     */
    public function loadAll(
        string $orderBy = "id",
        string $orderDirection = "ASC",
        ?array $limitFields = null
    ): SetsLoadReply {
        return $this->loadWithConfig(
            whereConfig: null,
            orderConfig: ["enabled" => true, "byField" => $orderBy, "dir" => $orderDirection],
            limitFields: $limitFields
        );
    }


    /**
     * loadWithConfig
     * loads a collection of entrys from the database with 1 call
     * please think about using limit fields if you wont be updating data
     * to reduce the memory footprint
     */
    public function loadWithConfig(
        ?array $whereConfig = null,
        ?array $orderConfig = null,
        ?array $optionsConfig = null,
        ?array $joinTables = null,
        ?array $limitFields = null
    ): SetsLoadReply {
        $this->makeWorker();
        if ($limitFields != null) {
            $this->limitFields($limitFields);
        }
        $basicConfig = ["table" => $this->worker->getTable()];
        if ($this->disableUpdates == true) {
            $basicConfig["fields"] = $this->limitedFields;
        }
        $loadWhereConfig = $this->worker->autoFillWhereConfig($whereConfig);
        if ($loadWhereConfig->status == false) {
            return new SetsLoadReply(message: $loadWhereConfig->message);
        }
        $whereConfig = $loadWhereConfig->data;
        $loadWhereConfig = null;
        // Cache missed, read from the DB
        $loadData = $this->sql->selectV2(
            basic_config: $basicConfig,
            order_config: $orderConfig,
            whereConfig: $whereConfig,
            options_config: $optionsConfig,
            joinTables: $joinTables
        );
        if ($loadData->status == false) {
            $this->addError(errorMessage: "Unable to load data: " . $loadData->message);
            return new SetsLoadReply(message: $this->myLastErrorBasic);
        }
        return $this->processLoad(loadData: $loadData);
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
            if ($id === null) {
                continue;
            }
            if (in_array($id, $uids) == false) {
                $uids[] = $id;
            }
        }
        if (count($uids) == 0) {
            $this->addError(errorMessage: "No ids sent!");
            return new SetsLoadReply(message: $this->myLastErrorBasic);
        }
        $typeCheck = $this->worker->getFieldType(fieldName: $fieldName, as_mysqli_code: true);
        if ($typeCheck == null) {
            $this->addError(errorMessage: "Invalid field: " . $fieldName);
            return new SetsLoadReply(message: $this->myLastErrorBasic);
        }
        return $this->loadWithConfig(whereConfig: [
            "fields" => [$fieldName],
            "matches" => ["IN"],
            "values" => [$uids],
            "types" => [$typeCheck],
        ]);
    }
    /**
     * processLoad
     * takes the reply from mysqli and fills out objects and builds the collection
     */
    protected function processLoad(
        SelectReply $loadData,
        int $version = 1,
        int $age = -1
    ): SetsLoadReply {
        if ($age == -1) {
            $age = time();
        }
        if ($loadData->status == false) {
            $this->addError(errorMessage: "loadData status is false");
            return new SetsLoadReply(message: $this->myLastErrorBasic);
        }
        $this->makeWorker();
        $oldCount = $this->getCount();
        foreach ($loadData->dataset as $entry) {
            $class = $this->getWorkerClass();
            $new_object = new $class($entry);
            if ($this->disableUpdates == true) {
                $new_object->noUpdates();
            }
            if ($new_object->isLoaded() == true) {
                $this->collected[$entry["id"]] = $new_object;
            }
        }
        $this->rebuildIndex();
        return new SetsLoadReply(message: "ok", status: true, items: $this->getCount() - $oldCount);
    }
}
