<?php

namespace YAPF\Framework\DbObjects\CollectionSet;

use YAPF\Framework\Responses\DbObjects\SetsLoadReply;
use YAPF\Framework\Responses\MySQLi\SelectReply;

abstract class CollectionSet extends CollectionSetBulk
{
    /**
     * loadMatching
     * @deprecated
     * please use use loadWithConfig this function will be going
     * away at some point.
     */
    public function loadMatching(array $input): SetsLoadReply
    {
        $whereConfig = [
            "fields" => array_keys($input),
            "values" => array_values($input),
        ];
        return $this->loadWithConfig($whereConfig);
    }

    /**
     * loadOnField
     * uses one field to load from the database with
     * for full control please use the method loadWithConfig
     */
    protected function loadOnField(
        string $field,
        $value,
        int $limit = 0,
        string $orderBy = "id",
        string $orderDirection = "DESC"
    ): SetsLoadReply {
        if (is_object($value) == true) {
            $errormsg = "Attempted to pass value as a object!";
            $this->addError($errormsg);
            return ["status" => false,"message" => "Attempted to pass a value as a object!"];
        }
        $whereConfig = [
            "fields" => [$field],
            "values" => [$value],
        ];
        $orderConfig = ["enabled" => true,"byField" => $orderBy,"dir" => $orderDirection];
        $optionsConfig = ["pageNumber" => 0,"limit" => $limit];
        return $this->loadWithConfig($whereConfig, $orderConfig, $optionsConfig);
    }
    /**
     * loadLimited
     * alias of loadNewest
     * paged loading support with limiters
     * for full control please use the method loadWithConfig
     */
    public function loadLimited(
        int $limit = 12,
        int $page = 0,
        string $orderBy = "id",
        string $orderDirection = "ASC",
        ?array $whereConfig = null
    ): SetsLoadReply {
        return $this->loadNewest($limit, $page, $orderBy, $orderDirection, $whereConfig);
    }
    /**
     * loadNewest
     * default setup is to order by id newest first.
     * for full control please use the method loadWithConfig
     */
    public function loadNewest(
        int $limit = 12,
        int $page = 0,
        string $orderBy = "id",
        string $orderDirection = "DESC",
        ?array $whereConfig = null
    ): SetsLoadReply {
        return $this->loadWithConfig(
            $whereConfig,
            ["enabled" => true,"byField" => $orderBy,"dir" => $orderDirection],
            ["pageNumber" => $page,"limit" => $limit]
        );
    }
    /**
     * loadAll
     * Loads everything it can get its hands
     * ordered by id ASC by default
     * for full control please use the method loadWithConfig
     */
    public function loadAll(string $orderBy = "id", string $orderDirection = "ASC"): SetsLoadReply
    {
        return $this->loadWithConfig(
            null,
            ["enabled" => true,"byField" => $orderBy,"dir" => $orderDirection]
        );
    }


    /**
     * loadWithConfig
     * Uses the select V2 system to load data
     * its magic!
     * see the v2 readme
     */
    public function loadWithConfig(
        ?array $whereConfig = null,
        ?array $order_config = null,
        ?array $options_config = null,
        ?array $join_tables = null
    ): SetsLoadReply {
        $this->makeWorker();
        $basic_config = ["table" => $this->worker->getTable()];
        if ($this->disableUpdates == true) {
            $basic_config["fields"] = $this->limitedFields;
        }
        $whereConfig = $this->worker->extendWhereConfig($whereConfig);
        // Cache support
        $hitCache = false;
        $currentHash = "";
        if ($this->cache != null) {
            $mergedData = $basic_config;
            if (is_array($join_tables) == true) {
                $mergedData = array_merge($basic_config, $join_tables);
            }
            $currentHash = $this->cache->getHash(
                $whereConfig,
                $order_config,
                $options_config,
                $mergedData,
                $this->getTable(),
                count($this->worker->getFields())
            );
            $hitCache = $this->cache->cacheValid($this->getTable(), $currentHash);
        }
        if ($hitCache == true) {
            // Valid data from cache!
            $loadResult = $this->cache->readHash($this->getTable(), $currentHash);
            if (is_array($loadResult) == true) {
                return $this->processLoad(new SelectReply("from cache", true, $loadResult));
            }
        }
        // Cache missed, read from the DB
        $loadData = $this->sql->selectV2(
            $basic_config,
            $order_config,
            $whereConfig,
            $options_config,
            $join_tables
        );
        if ($loadData->status == false) {
            $this->addError("Unable to load data: " . $loadData->message);
            return new SetsLoadReply($this->myLastErrorBasic);
        }
        if ($this->cache != null) {
            // push data to cache so we can avoid reading from DB as much
            $this->cache->writeHash(
                $this->worker->getTable(),
                $currentHash,
                $loadData->dataset,
                $this->cacheAllowChanged
            );
        }
        return $this->processLoad($loadData);
    }

    /**
     * loadIndexes
     * returns where fieldname value for the row is IN $values
     */
    protected function loadIndexes(string $fieldName = "id", array $values = []): SetsLoadReply
    {
        $this->makeWorker();
        $uids = [];
        foreach ($values as $id) {
            if (in_array($id, $uids) == false) {
                $uids[] = $id;
            }
        }
        if (count($uids) == 0) {
            $this->addError("No ids sent!");
            return new SetsLoadReply($this->myLastErrorBasic);
        }
        $typeCheck = $this->worker->getFieldType($fieldName, true);
        if ($typeCheck == null) {
            $this->addError("Invalid field: " . $fieldName);
            return new SetsLoadReply($this->myLastErrorBasic);
        }
        return $this->loadWithConfig([
            "fields" => [$fieldName],
            "matches" => ["IN"],
            "values" => [$uids],
            "types" => [$typeCheck],
        ]);
    }
    /**
     * processLoad
     * takes the reply from mysqli and fills out objects and builds the collection
     * @return mixed[] [status =>  bool, count => integer, message =>  string]
     */
    protected function processLoad(SelectReply $loadData): SetsLoadReply
    {
        if ($loadData->status == false) {
            $this->addError("loadData status is false");
            return new SetsLoadReply($this->myLastErrorBasic);
        }
        $this->makeWorker();
        $oldCount = $this->getCount();
        foreach ($loadData->dataset as $entry) {
            $new_object = new $this->workerClass($entry);
            if ($this->disableUpdates == true) {
                $new_object->noUpdates();
            }
            if ($new_object->isLoaded() == true) {
                $this->collected[$entry["id"]] = $new_object;
            }
        }
        $this->rebuildIndex();
        return new SetsLoadReply("ok", true, $this->getCount() - $oldCount);
    }
}
