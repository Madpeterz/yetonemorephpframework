<?php

namespace YAPF\Framework\DbObjects\GenClass;

use YAPF\Framework\Responses\DbObjects\CreateReply;
use YAPF\Framework\Responses\DbObjects\RemoveReply;
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
    public function loadMatching(array $input): bool
    {
        $whereConfig = [
            "fields" => array_keys($input),
            "values" => array_values($input),
        ];
        return $this->loadWithConfig($whereConfig);
    }

    /**
     * loadByField
     * loads a object that matchs in the DB on the field and value
     */
    public function loadByField(string $field_name, $field_value): bool
    {
        if (is_object($field_value) == true) {
            $errormsg = "Attempted to pass field_value as a object!";
            $this->addError($errormsg);
            return false;
        }
        $field_type = $this->getFieldType($field_name, true);
        if ($field_type == null) {
            $errormsg = "Attempted to get field type: " . $field_name . " but its not supported!";
            $this->addError($errormsg);
            return false;
        }
        $whereconfig = [
                "fields" => [$field_name],
                "matches" => ["="],
                "values" => [$field_value],
                "types" => [$field_type],
        ];
        return $this->loadWithConfig($whereconfig);
    }
    /**
     * loadID
     * loads the object from the database that matchs the id
     */
    public function loadID(int $id): bool
    {
        $whereconfig = [
            "fields" => ["id"],
            "matches" => ["="],
            "values" => [$id],
            "types" => ["i"],
        ];
        return $this->loadWithConfig($whereconfig);
    }

    /**
     * loadWithConfig
     * Fetchs data from the DB and hands it over to processLoad
     * where it matchs the whereconfig.
     * returns false if the class is disabled or the load fails
     */
    public function loadWithConfig(array $whereconfig): bool
    {
        if ($this->disabled == true) {
            $this->addError("unable to loadData This class is disabled");
            return false;
        }
        $basic_config = ["table" => $this->getTable()];
        if ($this->disableUpdates == true) {
            $basic_config["fields"] = $this->limitedFields;
        }
        $whereconfig = $this->extendWhereConfig($whereconfig);
        // Cache support
        $hitCache = false;
        $hashme = "";
        if ($this->cache != null) {
            $hashme = $this->cache->getHash(
                $whereconfig,
                ["single" => true],
                ["single" => true],
                $basic_config,
                $this->getTable(),
                count($this->getFields())
            );
            $hitCache = $this->cache->cacheVaild($this->getTable(), $hashme, true);
        }

        if ($hitCache == true) {
            // wooo vaild data from cache!
            $loadme = $this->cache->readHash($this->getTable(), $hashme);
            if (is_array($loadme) == true) {
                return $this->processLoad(new SelectReply("from cache", true, $loadme));
            }
        }
        $this->sql->setExpectedErrorFlag($this->expectedSqlLoadError);
        $load_data = $this->sql->selectV2($basic_config, null, $whereconfig);
        $this->sql->setExpectedErrorFlag(false);
        if ($this->cache != null) {
            // push data to cache so we can avoid reading from DB as much
            $this->cache->writeHash($this->getTable(), $hashme, $load_data->dataset, $this->cacheAllowChanged);
        }
        return $this->processLoad($load_data);
    }

    /**
     * extendWhereConfig
     * expands whereConfig to include types [as defined by object]
     * and matches [defaulting to =] if not given.
     * @return mixed[] whereConfig
     */
    public function extendWhereConfig(?array $whereConfig): ?array
    {
        if ($whereConfig === null) {
            return null;
        }
        if (array_key_exists("fields", $whereConfig) == false) {
            return $whereConfig;
        }
        if (array_key_exists("values", $whereConfig) == false) {
            return $whereConfig;
        }
        $expandMatchs = false;
        $expendTypes = false;
        if (array_key_exists("matches", $whereConfig) == false) {
            $expandMatchs = true;
            $whereConfig["matches"] = [];
        }
        if (array_key_exists("types", $whereConfig) == false) {
            $expendTypes = true;
            $whereConfig["types"] = [];
        }
        if (($expandMatchs == false) && ($expendTypes == false)) {
            return $whereConfig;
        }
        foreach ($whereConfig["fields"] as $field) {
            if ($expandMatchs == true) {
                $whereConfig["matches"][] = "=";
            }
            if ($expendTypes == true) {
                $whereConfig["types"][] = $this->getFieldType($field, true);
            }
        }
        return $whereConfig;
    }
    /**
     * processLoad
     * takes the result of the mysqli select
     * and fills in the objects dataset
     * returns true if needed checks are passed
     */
    protected function processLoad(SelectReply $load_data): bool
    {
        if ($load_data->status == true) {
            if ($load_data->entrys == 1) {
                $id_check_passed = true;
                $restore_dataset = $this->dataset;
                $this->setup($load_data->dataset[0]);
                if (($this->getId() <= 0) || ($this->getId() === null)) {
                    $id_check_passed = false;
                    $this->dataset = $restore_dataset;
                }
                return $id_check_passed;
            }
            $error_message = "Load error incorrect number of entrys expected 1 but got:";
            $error_message .= $load_data->entrys;
            $this->addError($error_message);
        }
        return false;
    }
    /**
     * removeMe
     * removes the loaded object from the database
     * and marks the object as unloaded by setting its id to -1
     * @return mixed[] [status =>  bool, message =>  string]
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
        $where_config = [
            "fields" => ["id"],
            "values" => [$this->getId()],
            "types" => ["i"],
            "matches" => ["="],
        ];
        $remove_status = $this->sql->removeV2($this->getTable(), $where_config);
        if ($remove_status->status == false) {
            $this->addError($remove_status->message);
            return new RemoveReply($this->myLastErrorBasic);
        }
        $this->dataset["id"]["value"] = -1;
        if ($this->cache != null) {
            $this->cache->markChangeToTable($this->getTable());
        }
        return new RemoveReply("ok", true, $remove_status->entrysRemoved);
    }
    /**
     * createEntry
     * create a new entry in the database for this object
     * once created it also sets the objects id field
     */
    public function createEntry(): CreateReply
    {
        if ($this->disableUpdates == true) {
            $this->addError("Attempt to update with limitFields enabled!");
            return new CreateReply($this->myLastErrorBasic);
        }
        if ($this->disabled == true) {
            $this->addError("This class is disabled.");
            return new CreateReply($this->myLastErrorBasic);
        }
        if (array_key_exists("id", $this->dataset) == false) {
            $this->addError("id field is required on the class to support create");
            return new CreateReply($this->myLastErrorBasic);
        }
        if (count($this->dataset) != count($this->save_dataset)) {
            $this->save_dataset = $this->dataset;
        }
        if (array_key_exists("id", $this->save_dataset) == false) {
            $this->addError("Attempt to create entry but save dataset does not have id field");
            return new CreateReply($this->myLastErrorBasic);
        }
        if ($this->save_dataset["id"]["value"] !== null) {
            $this->addError("Attempt to create entry but save dataset id is not null");
            return new CreateReply($this->myLastErrorBasic);
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
        if ($this->cache != null) {
            $this->cache->markChangeToTable($this->getTable());
        }
        $this->dataset["id"]["value"] = $return_dataset->newid;
        $this->save_dataset = $this->dataset;
        return new CreateReply("ok", true, $return_dataset->newid);
    }
    /**
     * updateEntry
     * updates changes to the object in the database
     */
    public function updateEntry(): UpdateReply
    {
        if ($this->disableUpdates == true) {
            $this->addError("Attempt to update with limitFields enabled!");
            return new UpdateReply($this->myLastErrorBasic);
        }
        if ($this->disabled == true) {
            $this->addError("This class is disabled.");
            return new UpdateReply($this->myLastErrorBasic);
        }
        if (array_key_exists("id", $this->save_dataset) == false) {
            $this->addError("Object does not have its id field set!");
            return new UpdateReply($this->myLastErrorBasic);
        }
        if ($this->save_dataset["id"]["value"] < 1) {
            $this->addError("Object id is not vaild for updates");
            return new UpdateReply($this->myLastErrorBasic);
        }

        $where_config = [
            "fields" => ["id"],
            "matches" => ["="],
            "values" => [$this->save_dataset["id"]["value"]],
            "types" => ["i"],
        ];
        $update_config = [
            "fields" => [],
            "values" => [],
            "types" => [],
        ];
        $had_error = false;
        $error_msg = "";

        foreach ($this->save_dataset as $key => $value) {
            if ($key != "id") {
                if (array_key_exists($key, $this->dataset) == false) {
                    $had_error = true;
                    $error_msg = "Key: " . $key . " is missing from dataset!";
                    break;
                }
                if (array_key_exists("value", $this->dataset[$key]) == false) {
                    $had_error = true;
                    $error_msg = "Key: " . $key . " is missing its value index!";
                    break;
                }
                if ($this->dataset[$key]["value"] != $this->save_dataset[$key]["value"]) {
                    $update_code = "i";
                    if ($this->dataset[$key]["type"] == "str") {
                        $update_code = "s";
                    } elseif ($this->dataset[$key]["type"] == "float") {
                        $update_code = "d";
                    }
                    $update_config["fields"][] = $key;
                    $update_config["values"][] = $this->dataset[$key]["value"];
                    $update_config["types"][] = $update_code;
                }
            }
        }
        if ($had_error == true) {
            $this->addError("request rejected: " . $error_msg);
            return new UpdateReply($this->myLastErrorBasic);
        }
        $expected_changes = count($update_config["fields"]);
        if ($expected_changes == 0) {
            $this->addError("No changes made");
            return new UpdateReply($this->myLastErrorBasic);
        }
        $reply = $this->sql->updateV2($this->getTable(), $update_config, $where_config, 1);
        if ($reply->status == true) {
            if ($this->cache != null) {
                $this->cache->markChangeToTable($this->getTable());
            }
        }
        return new UpdateReply("ok", true, $reply->entrysUpdated);
    }
}
