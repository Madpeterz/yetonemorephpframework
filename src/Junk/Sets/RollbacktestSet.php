<?php

namespace YAPF\Junk\Sets;

use YAPF\DbObjects\CollectionSet\CollectionSet as CollectionSet;
use YAPF\Junk\Models\Rollbacktest as Rollbacktest;

// Do not edit this file, rerun gen.php to update!
class RollbacktestSet extends CollectionSet
{
    public function __construct()
    {
        parent::__construct("YAPF\Junk\Models\Rollbacktest");
    }
    /**
     * getObjectByID
     * returns a object that matchs the selected id
     * returns null if not found
     * Note: Does not support bad Ids please use findObjectByField
     */
    public function getObjectByID($id): ?Rollbacktest
    {
        return parent::getObjectByID($id);
    }
    /**
     * getFirst
     * returns the first object in a collection
     */
    public function getFirst(): ?Rollbacktest
    {
        return parent::getFirst();
    }
    /**
     * getObjectByField
     * returns the first object in a collection that matchs the field and value checks
     */
    public function getObjectByField(string $fieldname, $value): ?Rollbacktest
    {
        return parent::getObjectByField($fieldname, $value);
    }
}
