<?php

namespace YAPF\Junk\Sets;

use YAPF\DbObjects\CollectionSet\CollectionSet as CollectionSet;
use YAPF\Junk\Models\Twintables1 as Twintables1;

// Do not edit this file, rerun gen.php to update!
class Twintables1Set extends CollectionSet
{
    public function __construct()
    {
        parent::__construct("YAPF\Junk\Models\Twintables1");
    }
    /**
     * getObjectByID
     * returns a object that matchs the selected id
     * returns null if not found
     * Note: Does not support bad Ids please use findObjectByField
     */
    public function getObjectByID($id): ?Twintables1
    {
        return parent::getObjectByID($id);
    }
    /**
     * getFirst
     * returns the first object in a collection
     */
    public function getFirst(): ?Twintables1
    {
        return parent::getFirst();
    }
    /**
     * getObjectByField
     * returns the first object in a collection that matchs the field and value checks
     */
    public function getObjectByField(string $fieldname, $value): ?Twintables1
    {
        return parent::getObjectByField($fieldname, $value);
    }
    /**
     * current
     * used by foreach to get the object should not be called directly
     */
    public function current(): Twintables1
    {
        return parent::current();
    }
    /**
     * uniqueIds
     * returns unique values from the collection matching that field
     * @return array<int>
     */
    public function uniqueIds(): array
    {
        return parent::uniqueArray("id");
    }
    /**
     * uniqueTitles
     * returns unique values from the collection matching that field
     * @return array<string>
     */
    public function uniqueTitles(): array
    {
        return parent::uniqueArray("title");
    }
    /**
     * uniqueMessages
     * returns unique values from the collection matching that field
     * @return array<string>
     */
    public function uniqueMessages(): array
    {
        return parent::uniqueArray("message");
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
     * loadByTitle
     * @return mixed[] [status =>  bool, count => integer, message =>  string]
    */
    public function loadByTitle(
        string $title, 
        int $limit = 0, 
        string $orderBy = "id", 
        string $orderDir = "DESC"
    ): array
    {
        return $this->loadByField(
            "title", 
            $title, 
            $limit, 
            $orderBy, 
            $orderDir
        );
    }
    /**
     * loadFromTitles
     * @return array<mixed> [status =>  bool, count => integer, message =>  string]
    */
    public function loadFromTitles(array $values): array
    {
        return $this->loadIndexs("title", $values);
    }
    /**
     * loadByMessage
     * @return mixed[] [status =>  bool, count => integer, message =>  string]
    */
    public function loadByMessage(
        string $message, 
        int $limit = 0, 
        string $orderBy = "id", 
        string $orderDir = "DESC"
    ): array
    {
        return $this->loadByField(
            "message", 
            $message, 
            $limit, 
            $orderBy, 
            $orderDir
        );
    }
    /**
     * loadFromMessages
     * @return array<mixed> [status =>  bool, count => integer, message =>  string]
    */
    public function loadFromMessages(array $values): array
    {
        return $this->loadIndexs("message", $values);
    }
    // Related loaders
}
