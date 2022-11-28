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
}
