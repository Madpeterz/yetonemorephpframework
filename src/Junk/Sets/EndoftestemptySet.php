<?php

namespace YAPF\Junk\Sets;

use YAPF\DbObjects\CollectionSet\CollectionSet as CollectionSet;
use YAPF\Junk\Models\Endoftestempty as Endoftestempty;

// Do not edit this file, rerun gen.php to update!
class EndoftestemptySet extends CollectionSet
{
    public function __construct()
    {
        parent::__construct("YAPF\Junk\Models\Endoftestempty");
    }
    /**
     * getObjectByID
     * returns a object that matchs the selected id
     * returns null if not found
     * Note: Does not support bad Ids please use findObjectByField
     */
    public function getObjectByID($id): ?Endoftestempty
    {
        return parent::getObjectByID($id);
    }
    /**
     * getFirst
     * returns the first object in a collection
     */
    public function getFirst(): ?Endoftestempty
    {
        return parent::getFirst();
    }
}