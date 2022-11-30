<?php

namespace YAPF\Framework\DbObjects\GenClass;

use Iterator;
use Throwable;
use YAPF\Framework\Core\SQLi\SqlConnectedClass as SqlConnectedClass;
use YAPF\Framework\Responses\DbObjects\CreateUidReply;
use YAPF\Framework\Responses\DbObjects\UpdateReply;

abstract class GenClassControl extends SqlConnectedClass implements Iterator
{
    // start Iterator
    protected $position = 0;
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Return the current field value in the selected pos
     * @return mixed The current field value.
     */
    public function current(): mixed
    {
        return $this->getField($this->fields[$this->position]);
    }

    public function key(): string
    {
        return $this->fields[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        if ($this->position < 0) {
            return false;
        }
        if ($this->position >= count($this->fields)) {
            return false;
        }
        return true;
    }

    // end Iterator


    protected bool $cacheAllowChanged = false;
    protected $use_table = "";
    protected $save_dataset = [];
    protected $dataset = [];
    protected $fields = [];
    protected $allow_set_field = true;

    protected bool $disableUpdates = false;
    protected ?array $limitedFields = null;

    /**
     * __construct
     * [Optional] takes a key => value array
     * where the key is the field
     * and sets on the object with these values,
     * you should avoid this and use the load[X] methods!
     */
    public function __construct(array $defaults = [])
    {
        if (count($defaults) > 0) {
            $this->setup($defaults);
        }
        parent::__construct();
        global $system;
        if ($this->disabled == false) {
            $this->cache = $system->getCacheDriver();
        }
    }

    public function limitFields(array $fields): void
    {
        if (in_array("id", $fields) == false) {
            $fields = array_merge(["id"], $fields);
        }
        $this->limitedFields = $fields;
        $this->noUpdates();
    }

    public function getUpdatesStatus(): bool
    {
        return $this->disableUpdates;
    }

    public function noUpdates(): void
    {
        $this->disableUpdates = true;
    }

    /**
     * createUID
     * public alias of overloadCreateUID
     * creates a UID based on the target field that does not exist in the datbase
     * Attempts this 3 times before it gives up.
     */
    public function createUID(string $onfield, int $length, int $attempts = 0): CreateUidReply
    {
        $feedValues = [time(), microtime(), rand(200, 300), $attempts];
        $testuid = substr(md5(implode(".", $feedValues)), 0, $length);
        $whereConfig = [
            "fields" => [$onfield],
            "values" => [$testuid],
            "types" => ["s"],
            "matches" => ["="],
        ];
        $count_check = $this->sql->basicCountV2($this->getTable(), $whereConfig);
        if ($count_check->status == false) {
            return new CreateUidReply("Unable to check if uid is in use");
        }
        if ($count_check->items != 0) {
            if ($attempts > 3) {
                return new CreateUidReply("created uid in use, please try again");
            }
            return $this->createUID($onfield, $length, ($attempts + 1));
        }
        return new CreateUidReply("ok", true, $testuid);
    }
    /**
     * fieldsHash
     * creates a sha256 hash imploded by || of the value of all fields
     * that are not in exclude_fields
     */
    public function fieldsHash(array $exclude_fields = ["id"]): string
    {
        $bits = [];
        $fields = $this->getFields();
        foreach ($fields as $fieldName) {
            if (in_array($fieldName, $exclude_fields) == false) {
                $bits[] = $this->getField($fieldName);
            }
        }
        return hash("sha256", implode("||", $bits));
    }

    /**
     * This function returns an array of the object's fields, with the fields that are in the
     * array excluded
     *
     * @param array ignoreFields an array of field names to ignore.
     * @param bool invertIgnore if true only fields in ignoreFields will be returned.
     * @return mixed[] string "field" => "field value"
    */
    public function objectToMappedArray(array $ignoreFields = [], bool $invertIgnore = false): array
    {
        $reply = [];
        foreach ($this->fields as $fieldName) {
            if (in_array($fieldName, $ignoreFields) == $invertIgnore) {
                $reply[$fieldName] = $this->getField($fieldName);
            }
        }
        return $reply;
    }
    /**
     * objectToValueArray
     * returns an aray of all values for this object
     * knida pointless I might remove this later
     * @return mixed[] [mixed,...]
     */
    public function objectToValueArray(array $ignoreFields = []): array
    {
        return array_values($this->objectToMappedArray($ignoreFields));
    }
    /**
     * hasField
     * checks if the object has the selected field
     */
    public function hasField(string $fieldName): bool
    {
        return in_array($fieldName, $this->fields);
    }
    /**
     * getFieldType
     * returns the field type as a string
     * or null if not found, null also creates an error
     */
    public function getFieldType(string $fieldName, bool $as_mysqli_code = false): ?string
    {
        if (in_array($fieldName, $this->fields) == false) {
            $error_meesage = " Attempting to read a fieldtype [" . $fieldName . "] has failed";
            $this->addError(get_class($this) . $error_meesage);
            return null;
        }
        if ($as_mysqli_code == true) {
            if ($this->dataset[$fieldName]["type"] == "str") {
                return "s";
            } elseif ($this->dataset[$fieldName]["type"] == "float") {
                return "d";
            }
            return "i";
        }
        return $this->dataset[$fieldName]["type"];
    }
    /**
     * getId
     * returns the ID for the object
     */
    public function getId(): ?int
    {
        return $this->getField("id");
    }
    /**
     * getFields
     * returns an array of all fields for the object
     * @return string[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }
    /**
     * isLoaded
     * returns a bool if the object is loaded from DB
     * Notes: Does not support custom Ids
     */
    public function isLoaded(): bool
    {
        if (in_array("id", $this->fields) == false) {
            return false;
        }
        if ($this->getField("id") < 1) {
            return false;
        }
        return true;
    }
    /**
     * getField
     * returns the value of a field
     * or null if not supported/not loaded,
     */
    protected function getField(string $fieldName): mixed
    {
        if (in_array($fieldName, $this->fields) == false) {
            $this->addError(get_class($this) . " Attempting to get field that does not exist");
            return null;
        }
        $value = $this->dataset[$fieldName]["value"];
        if ($value === null) {
            return null;
        }
        if ($this->dataset[$fieldName]["type"] == "int") {
            $value = intval($value);
        } elseif ($this->dataset[$fieldName]["type"] == "bool") {
            $value = in_array($value, [1,"1","true",true,"yes"], true);
        } elseif ($this->dataset[$fieldName]["type"] == "float") {
            $value = floatval($value);
        }
        return $value;
    }
    /**
     * getTable
     * returns the table assigned to this object
     */
    public function getTable(): string
    {
        return $this->use_table;
    }

    public function setCacheAllowChanged(bool $status = true): void
    {
        $this->cacheAllowChanged = $status;
    }
    protected bool $expectedSqlLoadError = false;

    public function expectedSqlLoadError(bool $setFlag = false): void
    {
        $this->expectedSqlLoadError = $setFlag;
    }
    public function disableAllowSetField(): void
    {
        $this->allow_set_field = false;
    }
    /**
     * setup
     * Fills in the dataset with a key => value array
     * used when first loading a object
     * returns true if there was no errors
     * unknown keys are skipped
     */
    public function setup(array $keyvalues): bool
    {
        $hasErrors = false;
        $saveDataset = $this->dataset;
        foreach ($keyvalues as $key => $value) {
            if (in_array($key, $this->fields) == false) {
                continue;
            }
            $this->dataset[$key]["value"] = $value;
        }
        if ($hasErrors == true) {
            $this->dataset = $saveDataset;
            return false;
        }
        $this->save_dataset = $this->dataset;
        return true;
    }
    /**
     * setTable
     * Sets the table used by the object
     * note: You should avoid using this unless you know
     * what your doing
     */
    public function setTable(string $tablename = ""): void
    {
        $this->addError("Warning: setTable called. if you expected this please ignore");
        $this->use_table = $tablename;
    }
    /**
     * updateField
     * Updates the live value of a object
     * call 'saveChanges' to apply the changes to the DB!
     * Note: Setting the ID can lead to weird side effects!
     */
    protected function updateField(string $fieldName, $value, bool $ignoreIdWarning = false): UpdateReply
    {
        if ($this->disableUpdates == true) {
            $this->addError("Attempt to update with limitFields enabled!");
            return new UpdateReply($this->myLastErrorBasic);
        }
        if (count($this->dataset) != count($this->save_dataset)) {
            $this->save_dataset = $this->dataset;
        }
        $check = $this->checkUpdateField($fieldName, $value, $ignoreIdWarning);
        if ($check->status == false) {
            return new UpdateReply($this->myLastErrorBasic);
        }
        $this->dataset[$fieldName]["value"] = $value;
        if ($this->getFieldType($fieldName) == "bool") {
            $this->dataset[$fieldName]["value"] = 0;
        }
        if (in_array($value, [1, "1", "true", true, "yes"], true) == true) {
            $this->dataset[$fieldName]["value"] = 1;
        }
        return new UpdateReply("value set", true, 1);
    }
    /**
     * checkUpdateField
     * checks if the update field request can be accepted
     */
    protected function checkUpdateField(string $fieldName, $value, bool $ignoreIdWarning = false): UpdateReply
    {
        if (is_object($value) == true) {
            $this->addError("System error: Attempt to put a object onto field: " . $fieldName);
            return new UpdateReply($this->myLastErrorBasic);
        }
        if (is_array($value) == true) {
            $this->addError("System error: Attempt to put a array onto field: " . $fieldName);
            return new UpdateReply($this->myLastErrorBasic);
        }
        if ($this->disabled == true) {
            $this->addError("This class is disabled");
            return new UpdateReply($this->myLastErrorBasic);
        }
        if ($this->allow_set_field == false) {
            $this->addError("update_field is not allowed for this object");
            return new UpdateReply($this->myLastErrorBasic);
        }
        if (in_array($fieldName, $this->fields) == false) {
            $this->addError("Sorry this object does not have the field: " . $fieldName);
            return new UpdateReply($this->myLastErrorBasic);
        }
        if (($fieldName == "id") && ($ignoreIdWarning == false)) {
            $this->addError("Sorry this object does not allow you to set the id field!");
            return new UpdateReply($this->myLastErrorBasic);
        }
        return new UpdateReply("ok", true);
    }

    /*
        bulkChange
        takes in a name => value pairs as an array
        passes that to the set function

        on failure rolls back any changes to the object
    */
    public function bulkChange(array $namevaluepairs): UpdateReply
    {
        if ($this->disableUpdates == true) {
            $this->addError("Attempt to update with limitFields enabled!");
            return new UpdateReply($this->myLastErrorBasic);
        }
        $rollback_savedataset = $this->save_dataset;
        $rollback_dataset = $this->dataset;
        $all_ok = true;
        $why_failed = "";
        $changes = 0;
        try {
            foreach ($namevaluepairs as $key => $value) {
                $functionname = "set" . ucfirst($key);
                if (method_exists($this, $functionname) == false) {
                    $why_failed = "Unknown key " . $key;
                    $all_ok = false;
                    break;
                }

                $status = $this->$functionname($value);
                if (is_object($status) == false) {
                    $why_failed = "reply from function " . $functionname . " should be an object";
                    $all_ok = false;
                    break;
                }
                if ($status->status == false) {
                    $why_failed = $status->message;
                    $all_ok = false;
                    break;
                }
                $changes++;
            }
        } catch (Throwable $e) {
            $why_failed = $e->getMessage();
            $all_ok = false;
        }
        if ($all_ok == false) {
            $this->addError($why_failed);
            $this->save_dataset = $rollback_savedataset;
            $this->dataset = $rollback_dataset;
            return new UpdateReply($this->myLastErrorBasic);
        }
        return new UpdateReply("ok", true, $changes);
    }

    /**
     * It returns true if the value of the field is the same as the default value of the field
     * if you are going to be checking multiple fields
     * please use getListDefaultFields
     */
    public function isDefault(string $field): bool
    {
        $class = get_class($this);
        $copy = new $class();
        $fields = $this->getFields();
        if (in_array($field, $fields) == false) {
            return false;
        }
        $functionnameget = "get" . ucfirst($field);
        if ($copy->$functionnameget() != $this->$functionnameget()) {
            return false;
        }
        return true;
    }

    /**
     * It returns an array of fields that are the same as the default values
     * @param checkFields An array of fields to check. If null, all fields will be checked.
     * @param array excludeFields An array of fields to exclude from the list of fields to check.
     * @return string[] a key value list of default fields and their values
     */
    public function getListDefaultFields(?array $checkFields = null, array $excludeFields = ["id"]): array
    {
        $class = get_class($this);
        $copy = new $class();
        $fields = $this->getFields();

        $testFields = $fields;
        if ($checkFields != null) {
            $testFields = [];
            foreach ($checkFields as $field) {
                if (in_array($field, $fields) == false) {
                    continue;
                }
                $testFields[] = $field;
            }
        }
        $fields = [];
        $fieldIsDefault = [];

        foreach ($testFields as $field) {
            if (in_array($field, $excludeFields) == true) {
                continue;
            }
            $functionnameget = "get" . ucfirst($field);
            if ($copy->$functionnameget() != $this->$functionnameget()) {
                continue;
            }
            $fieldIsDefault[$field] = $this->$functionnameget();
        }
        return $fieldIsDefault;
    }

    /*
        defaultValues

        forces the object to return to default values for all
        fields apart from id and any excluded fields

        on failure rolls back any changes to the object
    */
    public function defaultValues(array $excludeFields = []): bool
    {
        if ($this->disableUpdates == true) {
            $this->addError("Attempt to update with limitFields enabled!");
            return false;
        }
        $rollback_savedataset = $this->save_dataset;
        $rollback_dataset = $this->dataset;
        $excludeFields[] = "id";
        $class = get_class($this);
        $copy = new $class();
        $fields = $this->getFields();
        $all_ok = true;
        $why_failed = "";
        foreach ($fields as $field) {
            if (in_array($field, $excludeFields) == true) {
                continue;
            }
            $functionnameset = "set" . ucfirst($field);
            $functionnameget = "get" . ucfirst($field);
            if (method_exists($this, $functionnameset) == false) {
                $all_ok = false;
                $why_failed = "Missing function: " . $functionnameset;
                break;
            }
            if (method_exists($this, $functionnameget) == false) {
                $all_ok = false;
                $why_failed = "Missing function: " . $functionnameget;
                break;
            }
            $value = $copy->$functionnameget();
            $status = $this->$functionnameset($value);
            if (is_object($status) == false) {
                $why_failed = "reply from function " . $functionnameset . " should be an object";
                $all_ok = false;
                break;
            }
            if ($status->status == false) {
                $why_failed = $status->message;
                $all_ok = false;
                break;
            }
        }
        if ($all_ok == false) {
            $this->addError($why_failed);
            $this->save_dataset = $rollback_savedataset;
            $this->dataset = $rollback_dataset;
        }
        return $all_ok;
    }
}
