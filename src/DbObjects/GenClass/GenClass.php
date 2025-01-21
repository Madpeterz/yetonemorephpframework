<?php

namespace YAPF\Framework\DbObjects\GenClass;

abstract class GenClass extends GenClassDB
{
    public ?int $_Id
    {
        get => $this->getField(fieldName: "id");
        set { }
    }
    /**
     * HasAny
     * using a fast count query
     * check to see if there are ANY objects in the database
     * returns true if more than zero
     */
    public function hasAny(): bool
    {
        $whereConfig = [
            "fields" => ["id"],
            "values" => [-1],
            "types" => ["i"],
            "matches" => [">="],
        ];
        $reply = $this->sql->basicCountV2($this->getTable(), $whereConfig);
        if ($reply->status == false) {
            return false;
        }
        if ($reply->items > 0) {
            return true;
        }
        return false;
    }

    public function makedisabled(): void
    {
        $this->disabled = true;
    }
}
