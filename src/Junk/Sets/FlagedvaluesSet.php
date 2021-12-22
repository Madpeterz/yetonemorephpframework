<?php

namespace YAPF\Junk\Sets;

use YAPF\DbObjects\CollectionSet\CollectionSet as CollectionSet;
use YAPF\Junk\Models\Flagedvalues as Flagedvalues;

// Do not edit this file, rerun gen.php to update!
class FlagedvaluesSet extends CollectionSet
{
    public function __construct()
    {
        parent::__construct("YAPF\Junk\Models\Flagedvalues");
    }
    /**
     * getObjectByID
     * returns a object that matchs the selected id
     * returns null if not found
     * Note: Does not support bad Ids please use findObjectByField
     */
    public function getObjectByID($id): ?Flagedvalues
    {
        return parent::getObjectByID($id);
    }
    /**
     * getFirst
     * returns the first object in a collection
     */
    public function getFirst(): ?Flagedvalues
    {
        return parent::getFirst();
    }
    /**
     * getObjectByField
     * returns the first object in a collection that matchs the field and value checks
     */
    public function getObjectByField(string $fieldname, $value): ?Flagedvalues
    {
        return parent::getObjectByField($fieldname, $value);
    }
    /**
     * current
     * used by foreach to get the object should not be called directly
     */
    public function current(): Flagedvalues
    {
        return parent::current();
    }
    /**
     * getUniqueIds
     * returns unique values from the collection matching that field
     * @return array<int>
     */
    public function getUniqueIds(): array
    {
        return parent::getUniqueArray("id");
    }
    /**
     * getUniqueNames
     * returns unique values from the collection matching that field
     * @return array<string>
     */
    public function getUniqueNames(): array
    {
        return parent::getUniqueArray("name");
    }
    /**
     * getUniqueValues
     * returns unique values from the collection matching that field
     * @return array<string>
     */
    public function getUniqueValues(): array
    {
        return parent::getUniqueArray("value");
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
     * loadByName
     * @return mixed[] [status =>  bool, count => integer, message =>  string]
    */
    public function loadByName(
        string $name, 
        int $limit = 0, 
        string $orderBy = "id", 
        string $orderDir = "DESC"
    ): array
    {
        return $this->loadByField(
            "name", 
            $name, 
            $limit, 
            $orderBy, 
            $orderDir
        );
    }
    /**
     * loadFromNames
     * @return array<mixed> [status =>  bool, count => integer, message =>  string]
    */
    public function loadFromNames(array $values): array
    {
        return $this->loadIndexs("name", $values);
    }
    /**
     * loadByValue
     * @return mixed[] [status =>  bool, count => integer, message =>  string]
    */
    public function loadByValue(
        string $value, 
        int $limit = 0, 
        string $orderBy = "id", 
        string $orderDir = "DESC"
    ): array
    {
        return $this->loadByField(
            "value", 
            $value, 
            $limit, 
            $orderBy, 
            $orderDir
        );
    }
    /**
     * loadFromValues
     * @return array<mixed> [status =>  bool, count => integer, message =>  string]
    */
    public function loadFromValues(array $values): array
    {
        return $this->loadIndexs("value", $values);
    }
    // Related loaders
}
