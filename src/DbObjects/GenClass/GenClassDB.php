<?php

namespace YAPF\Framework\DbObjects\GenClass;

use YAPF\Framework\Responses\DbObjects\AutoFillReply;
use YAPF\Framework\Responses\DbObjects\CreateReply;
use YAPF\Framework\Responses\DbObjects\RemoveReply;
use YAPF\Framework\Responses\DbObjects\SingleLoadReply;
use YAPF\Framework\Responses\DbObjects\UpdateReply;
use YAPF\Framework\Responses\MySQLi\SelectReply;

abstract class GenClassDB extends GenClassControl
{
    /**
     * loadMatching
     * a very limited loading system
     * takes the keys as fields, and values as values
     * then passes that to loadWithConfig.
     */
    public function loadMatching(?array $input): SingleLoadReply
    {
        if ($input === null) {
            $this->addError("Input array is null");
            return new SingleLoadReply($this->myLastErrorBasic);
        } elseif (count($input) == 0) {
            $this->addError("Input array is empty");
            return new SingleLoadReply($this->myLastErrorBasic);
        }
        $whereConfig = [
            "fields" => array_keys($input),
            "values" => array_values($input),
        ];
        return $this->loadWithConfig($whereConfig);
    }

    /**
     * loadByField
     * loads a object that matches in the DB on the field and value
     */
    public function loadByField(string $fieldName, $field_value): SingleLoadReply
    {
        if (is_object($field_value) == true) {
            $this->addError("Attempted to pass field_value as a object!");
            return new SingleLoadReply($this->myLastErrorBasic);
        }
        $field_type = $this->getFieldType($fieldName, true);
        if ($field_type == null) {
            $this->addError("Attempted to get field type: " . $fieldName . " but its not supported!");
            return new SingleLoadReply($this->myLastErrorBasic);
        }
        $whereConfig = [
                "fields" => [$fieldName],
                "matches" => ["="],
                "values" => [$field_value],
                "types" => [$field_type],
        ];
        return $this->loadWithConfig($whereConfig);
    }
    /**
     * loadId
     * loads the object from the database that matches the id
     */
    public function loadId(?int $id): SingleLoadReply
    {
        if ($id == false) {
            $this->addError("Attempted to loadId but id is null!");
            return new SingleLoadReply($this->myLastErrorBasic);
        } elseif ($id < 1) {
            $this->addError("Attempted to loadId but id is less than one!");
            return new SingleLoadReply($this->myLastErrorBasic);
        }
        $whereConfig = [
            "fields" => ["id"],
            "matches" => ["="],
            "values" => [$id],
            "types" => ["i"],
        ];
        return $this->loadWithConfig($whereConfig);
    }

    /**
     * loadWithConfig
     * Fetch data from the DB and hands it over to processLoad
     * where it matches the whereConfig.
     * returns false if the class is disabled or the load fails
     */
    public function loadWithConfig(array $whereConfig): SingleLoadReply
    {
        if ($this->disabled == true) {
            $this->addError("unable to loadData This class is disabled");
            return new SingleLoadReply($this->myLastErrorBasic);
        }
        $basic_config = ["table" => $this->getTable()];
        if ($this->disableUpdates == true) {
            $basic_config["fields"] = $this->limitedFields;
        }
        $loadWhereConfig = $this->autoFillWhereConfig($whereConfig);
        if ($loadWhereConfig->status == false) {
            return new SingleLoadReply($loadWhereConfig->message);
        }
        $whereConfig = $loadWhereConfig->data;
        // Cache support
        if ($this->cache != null) {
            $currentHash = $this->cache->getHash(
                $this->getTable(),
                count($this->getFields()),
                true,
                $whereConfig,
                ["single" => true],
                ["single" => true],
                $basic_config
            );
            $hitCache = $this->cache->readHash($this->getTable(), $currentHash, true);
            if (is_array($hitCache) == true) {
                return $this->processLoad(new SelectReply("from cache", true, $hitCache));
            }
        }

        $this->sql->setExpectedErrorFlag($this->expectedSqlLoadError);
        $loadData = $this->sql->selectV2($basic_config, null, $whereConfig);
        $this->sql->setExpectedErrorFlag(false);
        if ($this->cache != null) {
            // push data to cache so we can avoid reading from DB as much
            $this->cache->writeHash($this->getTable(), $currentHash, $loadData->dataset, true);
        }
        return $this->processLoad($loadData);
    }

    protected function checkAutoFillWhereConfig(?array $whereConfig): bool
    {
        if ($whereConfig === null) {
            return false;
        }
        if (array_key_exists("fields", $whereConfig) == false) {
            return false;
        }
        if (array_key_exists("values", $whereConfig) == false) {
            return false;
        }
        return true;
    }

    /**
     * autoFillWhereConfig
     * expands whereConfig to include types [as defined by object]
     * and matches [defaulting to =] if not given.
     */
    public function autoFillWhereConfig(?array $whereConfig): AutoFillReply
    {
        if ($this->checkAutoFillWhereConfig($whereConfig) == false) {
            return new AutoFillReply("not used", true);
        }
        $expandMatches = false;
        $expendTypes = false;
        if (array_key_exists("matches", $whereConfig) == false) {
            $expandMatches = true;
            $whereConfig["matches"] = [];
        }
        if (array_key_exists("types", $whereConfig) == false) {
            $expendTypes = true;
            $whereConfig["types"] = [];
        }
        if (($expandMatches == false) && ($expendTypes == false)) {
            return new AutoFillReply("no changes made", true, $whereConfig);
        }
        foreach ($whereConfig["fields"] as $field) {
            if ($expandMatches == true) {
                $whereConfig["matches"][] = "=";
            }
            if ($expendTypes == true) {
                $typeCode = $this->getFieldType($field, true);
                if ($typeCode === null) {
                    return new AutoFillReply("Failed: getFieldType");
                }
                $whereConfig["types"][] = $typeCode;
            }
        }
        return new AutoFillReply("ok", true, $whereConfig);
    }
    /**
     * processLoad
     * takes the result of the mysqli select
     * and fills in the objects dataset
     * returns true if needed checks are passed
     */
    protected function processLoad(SelectReply $loadData): SingleLoadReply
    {
        if ($loadData->status == false) {
            $this->addError($loadData->message);
            return new SingleLoadReply($this->getLastErrorBasic());
        }
        if ($loadData->items != 1) {
            $error_message = "Load error incorrect number of items expected 1 but got:";
            $error_message .= $loadData->items;
            $this->addError($error_message);
            return new SingleLoadReply($this->getLastErrorBasic());
        }
        $restore_dataset = $this->dataset;
        $this->setup($loadData->dataset[0]);
        if (($this->getId() <= 0) || ($this->getId() === null)) {
            $this->dataset = $restore_dataset;
            $this->addError("Invalid Id passed to processLoad!");
            return new SingleLoadReply($this->getLastErrorBasic());
        }
        return new SingleLoadReply("Ok", true);
    }
    /**
     * removeEntry
     * removes the loaded object from the database
     * and marks the object as unloaded by setting its id to -1
     */
    public function removeEntry(): RemoveReply
    {
        if ($this->disabled == true) {
            $this->addError("This class is disabled.");
            return new RemoveReply($this->myLastErrorBasic);
        }
        if ($this->getId() < 1) {
            $this->addError("this object is not loaded!");
            return new RemoveReply($this->myLastErrorBasic);
        }
        $whereConfig = [
            "fields" => ["id"],
            "values" => [$this->getId()],
            "types" => ["i"],
            "matches" => ["="],
        ];
        $remove_status = $this->sql->removeV2($this->getTable(), $whereConfig);
        if ($remove_status->status == false) {
            $this->addError($remove_status->message);
            return new RemoveReply($this->myLastErrorBasic);
        }
        $this->dataset["id"]["value"] = -1;
        $this->touchCacheTable();
        return new RemoveReply("ok", true, $remove_status->itemsRemoved);
    }

    protected function checkCreateEntry(): CreateReply
    {
        if ($this->disableUpdates == true) {
            $this->addError("Attempt to update with limitFields enabled!");
            return new CreateReply($this->myLastErrorBasic);
        } elseif ($this->disabled == true) {
            $this->addError("This class is disabled.");
            return new CreateReply($this->myLastErrorBasic);
        } elseif (array_key_exists("id", $this->dataset) == false) {
            $this->addError("id field is required on the class to support create");
            return new CreateReply($this->myLastErrorBasic);
        } elseif (count($this->dataset) != count($this->save_dataset)) {
            $this->save_dataset = $this->dataset;
        } elseif (array_key_exists("id", $this->save_dataset) == false) {
            $this->addError("Attempt to create entry but save dataset does not have id field");
            return new CreateReply($this->myLastErrorBasic);
        } elseif ($this->save_dataset["id"]["value"] !== null) {
            $this->addError("Attempt to create entry but save dataset id is not null");
            return new CreateReply($this->myLastErrorBasic);
        }
        return new CreateReply("continue", true);
    }

    /**
     * createEntry
     * create a new entry in the database for this object
     * once created it also sets the objects id field
     */
    public function createEntry(): CreateReply
    {
        $checks = $this->checkCreateEntry();
        if ($checks->status == false) {
            return $checks;
        }
        $fields = [];
        $values = [];
        $types = [];
        foreach ($this->dataset as $key => $value) {
            if ($key == "id") {
                continue;
            }
            $value = $this->dataset[$key]["value"];
            $fields[] = $key;
            $update_code = "i";
            if ($this->dataset[$key]["type"] == "str") {
                $update_code = "s";
            } elseif ($this->dataset[$key]["type"] == "float") {
                $update_code = "d";
            }
            $values[] = $value;
            $types[] = $update_code;
        }
        $config = [
            "table" => $this->getTable(),
            "fields" => [],
            "values" => [],
            "types" => [],
        ];
        if (count($fields) != 0) {
            $config["fields"] = $fields;
            $config["values"] = $values;
            $config["types"] = $types;
        }
        $return_dataset = $this->sql->addV2($config);
        if ($return_dataset->status == false) {
            $this->addError($return_dataset->message);
            return new CreateReply($this->myLastErrorBasic);
        }
        $this->touchCacheTable();
        $this->dataset["id"]["value"] = $return_dataset->newId;
        $this->save_dataset = $this->dataset;
        return new CreateReply("ok", true, $return_dataset->newId);
    }

    protected function checkUpdateEntry(): UpdateReply
    {
        if ($this->disableUpdates == true) {
            $this->addError("Attempt to update with limitFields enabled!");
            return new UpdateReply($this->myLastErrorBasic);
        } elseif ($this->disabled == true) {
            $this->addError("This class is disabled.");
            return new UpdateReply($this->myLastErrorBasic);
        } elseif (array_key_exists("id", $this->save_dataset) == false) {
            $this->addError("Object does not have its id field set!");
            return new UpdateReply($this->myLastErrorBasic);
        } elseif ($this->save_dataset["id"]["value"] < 1) {
            $this->addError("Object id is not valid for updates");
            return new UpdateReply($this->myLastErrorBasic);
        }
        return new UpdateReply("continue", true);
    }


    /**
     * updateEntry
     * updates changes to the object in the database
     */
    public function updateEntry(): UpdateReply
    {
        $reply = $this->checkUpdateEntry();
        if ($reply->status == false) {
            return $reply;
        }

        $whereConfig = [
            "fields" => ["id"],
            "matches" => ["="],
            "values" => [$this->save_dataset["id"]["value"]],
            "types" => ["i"],
        ];
        $updateConfig = [
            "fields" => [],
            "values" => [],
            "types" => [],
        ];
        $had_error = false;
        $error_msg = "";
        foreach ($this->save_dataset as $key => $value) {
            if ($key == "id") {
                continue;
            }
            if (array_key_exists($key, $this->dataset) == false) {
                $had_error = true;
                $error_msg = "Key: " . $key . " is missing from dataset!";
                break;
            } elseif (array_key_exists("value", $this->dataset[$key]) == false) {
                $had_error = true;
                $error_msg = "Key: " . $key . " is missing its value index!";
                break;
            } elseif ($this->dataset[$key]["value"] != $value) {
                $update_code = "i";
                if ($this->dataset[$key]["type"] == "str") {
                    $update_code = "s";
                } elseif ($this->dataset[$key]["type"] == "float") {
                    $update_code = "d";
                }
                $updateConfig["fields"][] = $key;
                $updateConfig["values"][] = $this->dataset[$key]["value"];
                $updateConfig["types"][] = $update_code;
            }
        }
        if ($had_error == true) {
            $this->addError("request rejected: " . $error_msg);
            return new UpdateReply($this->myLastErrorBasic);
        }
        $expected_changes = count($updateConfig["fields"]);
        if ($expected_changes == 0) {
            $this->addError("No changes made");
            return new UpdateReply($this->myLastErrorBasic);
        }
        $reply = $this->sql->updateV2($this->getTable(), $updateConfig, $whereConfig, 1);
        if ($reply->status == false) {
            $this->addError($reply->message);
            return new UpdateReply($this->myLastErrorBasic);
        }
        $this->touchCacheTable();
        return new UpdateReply("ok", true, $reply->itemsUpdated);
    }

    protected function touchCacheTable(): void
    {
        if ($this->cache != null) {
            $this->cache->markChangeToTable($this->getTable());
        }
    }
}
