<?php

namespace YAPF\Framework\DbObjects\CollectionSet;

use Iterator;
use YAPF\Framework\DbObjects\GenClass\GenClass;

abstract class CollectionSet extends CollectionSetFunctions implements Iterator
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
     * Return the last element in the collection.
     * @return ?object The last value in the array.
     */
    public function getLast(): ?object
    {
        $value = null;
        foreach ($this->collected as $c) {
            $value = $c;
        }
        return $value;
    }
}
