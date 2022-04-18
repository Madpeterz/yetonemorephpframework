<?php

namespace YAPF\Framework\DbObjects\CollectionSet;

use Iterator;
use YAPF\Framework\DbObjects\GenClass\GenClass;

abstract class CollectionSetGet extends CollectionSetCore implements Iterator
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
     * getCount
     * returns the number of objects in this collection set
     */
    public function getCount(): int
    {
        return count($this->collected);
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
     * getTable
     * returns the table assigned to the worker
     */
    public function getTable(): string
    {
        $this->makeWorker();
        return $this->worker->getTable();
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
     * Note: Does not support bad Ids please use findObjectByField
     */
    public function getObjectByID($id): ?object
    {
        $this->makeWorker();
        if (array_key_exists($id, $this->collected) == true) {
            return $this->collected[$id];
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
     * This function takes an array of fields to ignore and an optional boolean to invert the ignore list.
     * It then loops through the collected entries and returns an array of the entries mapped to an array
     * of the fields to ignore
     *
     * @param array ignoreFields an array of fields to ignore when converting the object to an array.
     * @param bool invertIgnore If true, the ignoreFields will be inverted.
     * @return mixed[] [id => array of mapped object,...]
     */
    public function getCollectionToMappedArray(array $ignoreFields, bool $invertIgnore = false): array
    {
        $results = [];
        foreach ($this->collected as $key => $entry) {
            /** @var GenClass $entry */
            $results[$entry->getId()] = $entry->objectToMappedArray($ignoreFields, $invertIgnore);
        }
        return $results;
    }
}
