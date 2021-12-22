<?php

namespace YAPF\Junk\Sets;

use YAPF\DbObjects\CollectionSet\CollectionSet as CollectionSet;
use YAPF\Junk\Models\Counttoonehundo as Counttoonehundo;

// Do not edit this file, rerun gen.php to update!
class CounttoonehundoSet extends CollectionSet
{
    public function __construct()
    {
        parent::__construct("YAPF\Junk\Models\Counttoonehundo");
    }
    /**
     * getObjectByID
     * returns a object that matchs the selected id
     * returns null if not found
     * Note: Does not support bad Ids please use findObjectByField
     */
    public function getObjectByID($id): ?Counttoonehundo
    {
        return parent::getObjectByID($id);
    }
    /**
     * getFirst
     * returns the first object in a collection
     */
    public function getFirst(): ?Counttoonehundo
    {
        return parent::getFirst();
    }
    /**
     * getObjectByField
     * returns the first object in a collection that matchs the field and value checks
     */
    public function getObjectByField(string $fieldname, $value): ?Counttoonehundo
    {
        return parent::getObjectByField($fieldname, $value);
    }
    /**
     * current
     * used by foreach to get the object should not be called directly
     */
    public function current(): Counttoonehundo
    {
        return parent::current();
    }
    /**
     * getUniqueCvalues
     * returns unique values from the collection matching that field
     * @return array<int>
     */
    public function getUniqueCvalues(): array
    {
        return parent::getUniqueArray("cvalue");
    }
    // Loaders
    /**
     * loadById
     * @return mixed[] [status =>  bool, count => integer, message =>  string]
    */
    public function loadById(
        int $id, 
        int $limit = 0, 
        string $orderBy = "id", 
        string $orderDir = "DESC"
    ): array
    {
        return $this->loadByField(
            "id", 
            $id, 
            $limit, 
            $orderBy, 
            $orderDir
        );
    }
    /**
     * loadFromIds
     * @return array<mixed> [status =>  bool, count => integer, message =>  string]
    */
    public function loadFromIds(array $values): array
    {
        return $this->loadIndexs("id", $values);
    }
    /**
     * loadByCvalue
     * @return mixed[] [status =>  bool, count => integer, message =>  string]
    */
    public function loadByCvalue(
        int $cvalue, 
        int $limit = 0, 
        string $orderBy = "id", 
        string $orderDir = "DESC"
    ): array
    {
        return $this->loadByField(
            "cvalue", 
            $cvalue, 
            $limit, 
            $orderBy, 
            $orderDir
        );
    }
    /**
     * loadFromCvalues
     * @return array<mixed> [status =>  bool, count => integer, message =>  string]
    */
    public function loadFromCvalues(array $values): array
    {
        return $this->loadIndexs("cvalue", $values);
    }
    // Related loaders
}
