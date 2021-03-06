<?php

namespace YAPF\Junk\Sets;

use YAPF\DbObjects\CollectionSet\CollectionSet as CollectionSet;
use YAPF\Junk\Models\Endoftestwithupdates as Endoftestwithupdates;

// Do not edit this file, rerun gen.php to update!
class EndoftestwithupdatesSet extends CollectionSet
{
    public function __construct()
    {
        parent::__construct("YAPF\Junk\Models\Endoftestwithupdates");
    }
    /**
     * getObjectByID
     * returns a object that matchs the selected id
     * returns null if not found
     * Note: Does not support bad Ids please use findObjectByField
     */
    public function getObjectByID($id): ?Endoftestwithupdates
    {
        return parent::getObjectByID($id);
    }
    /**
     * getFirst
     * returns the first object in a collection
     */
    public function getFirst(): ?Endoftestwithupdates
    {
        return parent::getFirst();
    }
    /**
     * getObjectByField
     * returns the first object in a collection that matchs the field and value checks
     */
    public function getObjectByField(string $fieldname, $value): ?Endoftestwithupdates
    {
        return parent::getObjectByField($fieldname, $value);
    }
}
