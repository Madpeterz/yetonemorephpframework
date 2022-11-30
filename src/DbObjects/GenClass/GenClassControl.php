<?php

namespace YAPF\Framework\DbObjects\GenClass;

use Iterator;

abstract class GenClassControl extends GenClassFunctions implements Iterator
{
    // start Iterator
    protected $position = 0;
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Return the current field value in the selected pos
     * @return mixed The current field value.
     */
    public function current(): mixed
    {
        return $this->getField($this->fields[$this->position]);
    }

    public function key(): string
    {
        return $this->fields[$this->position];
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
        if ($this->position >= count($this->fields)) {
            return false;
        }
        return true;
    }

    // end Iterator

    /**
     * It returns an array of fields that are the same as the default values
     * @param checkFields An array of fields to check. If null, all fields will be checked.
     * @param array excludeFields An array of fields to exclude from the list of fields to check.
     * @return string[] a key value list of default fields and their values
     */
    public function getListDefaultFields(?array $checkFields = null, array $excludeFields = ["id"]): array
    {
        $class = get_class($this);
        $copy = new $class();
        $fields = $this->getFields();

        $testFields = $fields;
        if ($checkFields != null) {
            $testFields = [];
            foreach ($checkFields as $field) {
                if (in_array($field, $fields) == false) {
                    continue;
                }
                $testFields[] = $field;
            }
        }
        $fields = [];
        $fieldIsDefault = [];

        foreach ($testFields as $field) {
            if (in_array($field, $excludeFields) == true) {
                continue;
            }
            $functionnameget = "get" . ucfirst($field);
            if ($copy->$functionnameget() != $this->$functionnameget()) {
                continue;
            }
            $fieldIsDefault[$field] = $this->$functionnameget();
        }
        return $fieldIsDefault;
    }

    /**
     * It returns true if the value of the field is the same as the default value of the field
     * if you are going to be checking multiple fields
     * please use getListDefaultFields
     */
    public function isDefault(string $field): bool
    {
        $class = get_class($this);
        $copy = new $class();
        $fields = $this->getFields();
        if (in_array($field, $fields) == false) {
            return false;
        }
        $functionnameget = "get" . ucfirst($field);
        if ($copy->$functionnameget() != $this->$functionnameget()) {
            return false;
        }
        return true;
    }
}
