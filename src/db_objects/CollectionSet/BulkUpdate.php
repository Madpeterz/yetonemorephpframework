<?php

namespace YAPF\DB_OBJECTS;

abstract class CollectionSetBulkUpdate extends CollectionSetGet
{
    /**
     * updateSingleFieldForCollection [E_USER_DEPRECATED]
     * please use: updateFieldInCollection
     * Updates all objects in the collection's value
     * for the selected field
     * @return mixed[] [status =>  bool, message =>  string]
     */
    public function updateSingleFieldForCollection(string $update_field, $new_value): array
    {
        $errormsg = "updateSingleFieldForCollection is being phased out please use updateFieldInCollection";
        trigger_error($errormsg, E_USER_DEPRECATED);
        return $this->updateFieldInCollection($update_field, $new_value);
    }
    /**
     * updateFieldInCollection
     * Updates all objects in the collection's value
     * for the selected field
     * @return mixed[] [status =>  bool, message =>  string]
     */
    public function updateFieldInCollection(string $update_field, $new_value): array
    {
        return $this->updateMultipleFieldsForCollection([$update_field], [$new_value]);
    }
    /**
     * updateMultipleMakeUpdateConfig
     * processes the update_fields and new_values arrays into
     * a update config
     * used by: updateMultipleFieldsForCollection
     * @return mixed[] [status => bool, dataset => mixed[] [fields => string[],values => mixed[], types => string[]]]
     */
    protected function updateMultipleMakeUpdateConfig(array $update_fields, array $new_values): array
    {
        $this->makeWorker();
        $update_config = [
            "fields" => [],
            "values" => [],
            "types" => [],
        ];
        $all_ok = true;
        $loop = 0;
        while ($loop < count($update_fields)) {
            $lookup = "get_" . $update_fields[$loop];
            if (method_exists($worker, $lookup) == false) {
                $all_ok = false;
                break;
            }
            $field_type = $worker->getFieldType($update_fields[$loop], true);
            if ($field_type == null) {
                $all_ok = false;
                break;
            }
            $update_config["fields"][] = $update_fields[$loop];
            $update_config["values"][] = $new_values[$loop];
            $update_config["types"][] = $field_type;
            $loop++;
        }
        return ["status" => $all_ok, "dataset" => $update_config];
    }
    /**
     * updateMultipleGetUpdatedIds
     * using the fields that have changes
     * it builds an array of ids that need to have
     * the update applyed to them and the total number of
     * entrys that need to be updated
     * @return mixed[] [changes => integer,changed_ids => integer[]]
     */
    protected function updateMultipleGetUpdatedIds(array $update_fields, array $new_values): array
    {
        $expected_changes = 0;
        $changed_ids = [];
        $ids = $this->getAllIds();
        $total_update_fields = count($update_fields);
        foreach ($ids as $entry_id) {
            $localworker = $this->collected[$entry_id];
            $loop2 = 0;
            while ($loop2 < $total_update_fields) {
                $lookup = "get_" . $update_fields[$loop2];
                if ($localworker->$lookup() != $new_values[$loop2]) {
                    $expected_changes++;
                    $changed_ids[] = $entry_id;
                    break;
                }
                $loop2++;
            }
        }
        return ["changes" => $expected_changes , "changed_ids" => $changed_ids];
    }
    /**
     * updateMultipleApplyChanges
     * applys the new values for each field to the collection
     */
    protected function updateMultipleApplyChanges(array $update_fields, array $new_values): void
    {
        $ids = $this->getallIds();
        $total_update_fields = count($update_fields);
        foreach ($ids as $entry_id) {
            $localworker = $this->collected[$entry_id];
            $loop2 = 0;
            while ($loop2 < $total_update_fields) {
                $applyer = "set_" . $update_fields[$loop2];
                $localworker->$applyer($new_values[$loop2]);
                $loop2++;
            }
        }
    }
    /**
     * updateMultipleFieldsForCollection
     * using the fields and values updates the collection
     * and applys the changes to the database.
     * @return mixed[] [status =>  bool, message =>  string]
     */
    public function updateMultipleFieldsForCollection(array $update_fields, array $new_values): array
    {
        $this->makeWorker();
        if ($this->getCount() <= 0) {
            $error_msg = "Nothing loaded in collection";
            return ["status" => false, "changes" => 0, "message" => $error_msg];
        }
        if (count($update_fields) <= 0) {
            $error_msg = "No fields being updated!";
            return ["status" => false, "changes" => 0, "message" => $error_msg];
        }
        $ready_update_config = $this->updateMultipleMakeUpdateConfig($update_fields, $new_values);
        if ($ready_update_config["status"] == false) {
            $error_msg = "Unable to create update config";
            return ["status" => false, "changes" => 0, "message" => $error_msg];
        }
        $change_config = $this->updateMultipleGetUpdatedIds($update_fields, $new_values);
        if ($change_config["changes"] <= 0) {
            $error_msg = "No changes made";
            return ["status" => true, "changes" => 0, "message" => $error_msg];
        }
        $update_config = $ready_update_config["dataset"];
        $where_config = [
            "fields" => ["id"],
            "matches" => ["IN"],
            "values" => $change_config["changed_ids"],
            "types" => ["i"],
        ];
        $table = $this->worker->getTable();
        $total_changes = $change_config["changes"];
        unset($change_config);
        unset($ready_update_config);
        $update_status = $this->sql->updateV2($table, $update_config, $where_config, $total_changes);
        if ($update_status["status"] == true) {
            $this->updateMultipleApplyChanges($update_fields, $new_values);
            return $update_status;
        }
        $error_msg = "Update failed because:" . $update_status["message"];
        return ["status" => false, "changes" => 0, "message" => $error_msg];
    }
}
