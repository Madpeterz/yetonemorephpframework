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
    /**
     * current
     * used by foreach to get the object should not be called directly
     */
    public function current(): Endoftestwithupdates
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
     * uniqueUsernames
     * returns unique values from the collection matching that field
     * @return array<string>
     */
    public function uniqueUsernames(): array
    {
        return parent::uniqueArray("username");
    }
    /**
     * uniqueOldusernames
     * returns unique values from the collection matching that field
     * @return array<string>
     */
    public function uniqueOldusernames(): array
    {
        return parent::uniqueArray("oldusername");
    }
    /**
     * uniqueBanneds
     * returns unique values from the collection matching that field
     * @return array<bool>
     */
    public function uniqueBanneds(): array
    {
        return parent::uniqueArray("banned");
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
     * loadByUsername
     * @return mixed[] [status =>  bool, count => integer, message =>  string]
    */
    public function loadByUsername(
        string $username, 
        int $limit = 0, 
        string $orderBy = "id", 
        string $orderDir = "DESC"
    ): array
    {
        return $this->loadByField(
            "username", 
            $username, 
            $limit, 
            $orderBy, 
            $orderDir
        );
    }
    /**
     * loadFromUsernames
     * @return array<mixed> [status =>  bool, count => integer, message =>  string]
    */
    public function loadFromUsernames(array $values): array
    {
        return $this->loadIndexs("username", $values);
    }
    /**
     * loadByOldusername
     * @return mixed[] [status =>  bool, count => integer, message =>  string]
    */
    public function loadByOldusername(
        string $oldusername, 
        int $limit = 0, 
        string $orderBy = "id", 
        string $orderDir = "DESC"
    ): array
    {
        return $this->loadByField(
            "oldusername", 
            $oldusername, 
            $limit, 
            $orderBy, 
            $orderDir
        );
    }
    /**
     * loadFromOldusernames
     * @return array<mixed> [status =>  bool, count => integer, message =>  string]
    */
    public function loadFromOldusernames(array $values): array
    {
        return $this->loadIndexs("oldusername", $values);
    }
    /**
     * loadByBanned
     * @return mixed[] [status =>  bool, count => integer, message =>  string]
    */
    public function loadByBanned(
        bool $banned, 
        int $limit = 0, 
        string $orderBy = "id", 
        string $orderDir = "DESC"
    ): array
    {
        return $this->loadByField(
            "banned", 
            $banned, 
            $limit, 
            $orderBy, 
            $orderDir
        );
    }
    /**
     * loadFromBanneds
     * @return array<mixed> [status =>  bool, count => integer, message =>  string]
    */
    public function loadFromBanneds(array $values): array
    {
        return $this->loadIndexs("banned", $values);
    }
    // Related loaders
}