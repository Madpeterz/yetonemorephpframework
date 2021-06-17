<?php

namespace YAPF\Junk\Sets;

use YAPF\DbObjects\CollectionSet\CollectionSet as CollectionSet;
use YAPF\Junk\Models\Twintables2 as Twintables2;

// Do not edit this file, rerun gen.php to update!
class Twintables2Set extends CollectionSet
{
    public function __construct()
    {
        parent::__construct("YAPF\Junk\Models\Twintables2");
    }
    /**
     * getObjectByID
     * returns a object that matchs the selected id
     * returns null if not found
     * Note: Does not support bad Ids please use findObjectByField
     */
    public function getObjectByID($id): ?Twintables2
    {
        return parent::getObjectByID($id);
    }
    /**
     * getFirst
     * returns the first object in a collection
     */
    public function getFirst(): ?Twintables2
    {
        return parent::getFirst();
    }
    /**
     * getFirst
     * returns the first object in a collection
     */
    public function getObjectByField(string $fieldname, $value): ?Twintables2
    {
        return parent::getObjectByField($fieldname, $value);
    }
}
