<?php

namespace YAPF\Framework\DbObjects\CollectionSet;

use YAPF\Framework\Responses\DbObjects\MultiUpdateReply;
use YAPF\Framework\Responses\DbObjects\RemoveReply;

abstract class CollectionSetBulk extends CollectionSetGet
{
    /**
     * purgeCollection
     * Removes all objects from the database that are in the collection
     */
    public function purgeCollection(): RemoveReply
    {
        $this->makeWorker();
        if ($this->getCount() == 0) {
            return new RemoveReply("Collection empty to start with", true, 0);
        }
        $where_config = [
            "fields" => ["id"],
            "values" => [$this->getAllIds()],
            "types" => ["i"],
            "matches" => ["IN"],
        ];
        $remove_status = $this->sql->removeV2($this->getTable(), $where_config);
        $status = false;
        $removed_entrys = 0;

        if ($remove_status->status == false) {
            $this->addError($remove_status->message);
            return new RemoveReply($this->myLastErrorBasic);
        }
        if ($remove_status->entrysRemoved != $this->getCount()) {
            $this->addError("Incorrect number of entrys removed");
            return new RemoveReply($this->myLastErrorBasic);
        }
        if ($this->cache != null) {
            $this->cache->markChangeToTable($this->getTable());
        }
        return new RemoveReply("ok", true, $remove_status->entrysRemoved);
    }

    /**
     * updateFieldInCollection
     * Updates all objects in the collection's value
     * for the selected field
     */
    public function updateFieldInCollection(string $update_field, $new_value): MultiUpdateReply
    {
        if ($this->disableUpdates == true) {
            $this->addError("Attempt to update with disableUpdates enabled!");
            return new MultiUpdateReply($this->myLastErrorBasic);
        }
        return $this->updateMultipleFieldsForCollection([$update_field], [$new_value]);
    }
    /**
     * updateMultipleMakeUpdateConfig
     * processes the update_fields and new_values arrays into
     * a update config
     * used by: updateMultipleFieldsForCollection
     * @return mixed[] [status => bool, message => string, dataset => mixed[]
     * [fields => string[],values => mixed[], types => string[]]]
     */
    protected function updateMultipleMakeUpdateConfig(array $update_fields, array $new_values): array
    {
        $this->makeWorker();
        $update_config = [
            "fields" => [],
            "values" => [],
            "types" => [],
        ];
        $message = "ok";
        $all_ok = true;
        $loop = 0;
        while ($loop < count($update_fields)) {
            $lookup = "get" . ucfirst($update_fields[$loop]);
            if (method_exists($this->worker, $lookup) == false) {
                $all_ok = false;
                $message = "Unable to find getter: " . $lookup;
                break;
            }
            $field_type = $this->worker->getFieldType($update_fields[$loop], false);
            if ($field_type == null) {
                $all_ok = false;
                $message = "Unable to find fieldtype: " . $update_fields[$loop];
                break;
            }

            $update_config["fields"][] = $update_fields[$loop];
            $update_config["values"][] = $new_values[$loop];
            $update_config["types"][] = $this->worker->getFieldType($update_fields[$loop], true);
            $loop++;
        }
        return ["status" => $all_ok, "dataset" => $update_config, "message" => $message];
    }

    /**
     * updateMultipleGetUpdatedIds
     * using the fields that have changes
     * it builds an array of ids that need to have
     * the update applyed to them and the total number of
     * entrys that need to be updated
     * @return int[] ids to change
     */
    protected function updateMultipleGetUpdatedIds(array $update_fields, array $new_values): array
    {
        $changed_ids = [];
        $ids = $this->getAllIds();
        $total_update_fields = count($update_fields);
        foreach ($ids as $entry_id) {
            $localworker = $this->collected[$entry_id];
            $loop2 = 0;
            while ($loop2 < $total_update_fields) {
                $lookup = "get" . ucfirst($update_fields[$loop2]);
                if ($localworker->$lookup() != $new_values[$loop2]) {
                    $changed_ids[] = $entry_id;
                    break;
                }
                $loop2++;
            }
        }
        return $changed_ids;
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
                $applyer = "set" . ucfirst($update_fields[$loop2]);
                $localworker->$applyer($new_values[$loop2]);
                $loop2++;
            }
        }
    }
    /**
     * updateMultipleFieldsForCollection
     * using the fields and values updates the collection
     * and applys the changes to the database.
     */
    public function updateMultipleFieldsForCollection(array $update_fields, array $new_values): MultiUpdateReply
    {
        if ($this->disableUpdates == true) {
            $this->addError("Attempt to update with limitFields enabled!");
            return new MultiUpdateReply($this->myLastErrorBasic);
        }
        $this->makeWorker();
        if ($this->getCount() <= 0) {
            $this->addError("Nothing loaded in collection");
            return new MultiUpdateReply($this->myLastErrorBasic);
        }
        if (count($update_fields) <= 0) {
            $this->addError("No fields being updated!");
            return new MultiUpdateReply($this->myLastErrorBasic);
        }
        $ready_update_config = $this->updateMultipleMakeUpdateConfig($update_fields, $new_values);
        if ($ready_update_config["status"] == false) {
            $this->addError($ready_update_config["message"]);
            return new MultiUpdateReply($this->myLastErrorBasic);
        }
        $change_config = $this->updateMultipleGetUpdatedIds($update_fields, $new_values);
        if (count($change_config) <= 0) {
            return new MultiUpdateReply("No changes made", true);
        }
        $update_config = $ready_update_config["dataset"];
        $where_config = [
            "fields" => ["id"],
            "matches" => ["IN"],
            "values" => [$change_config],
            "types" => ["i"],
        ];
        $table = $this->worker->getTable();
        $total_changes = count($change_config);
        unset($change_config);
        unset($ready_update_config);
        $update_status = $this->sql->updateV2($table, $update_config, $where_config, $total_changes);
        if ($update_status->status == false) {
            $this->addError("Update failed because:" . $update_status->message);
            return new MultiUpdateReply($this->myLastErrorBasic);
        }
        if ($this->cache != null) {
            $this->cache->markChangeToTable($this->getTable());
        }
        $this->updateMultipleApplyChanges($update_fields, $new_values);
        return new MultiUpdateReply("ok", true, $total_changes);
    }
}
