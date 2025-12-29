<?php

namespace YAPF\Framework\DbObjects\CollectionSet;

use Exception;
use YAPF\Framework\Responses\DbObjects\MultiUpdateReply;
use YAPF\Framework\Responses\DbObjects\RemoveReply;

abstract class CollectionSetBulk extends CollectionSetCore
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
        $whereConfig = [
            "fields" => ["id"],
            "values" => [$this->getAllIds()],
            "types" => ["i"],
            "matches" => ["IN"],
        ];
        $remove_status = $this->sql->removeV2($this->getTable(), $whereConfig);

        if ($remove_status->status == false) {
            $this->addError($remove_status->message);
            return new RemoveReply($this->myLastErrorBasic);
        }
        if ($remove_status->itemsRemoved != $this->getCount()) {
            $this->addError("Incorrect number of items removed");
            return new RemoveReply($this->myLastErrorBasic);
        }
        return new RemoveReply("ok", true, $remove_status->itemsRemoved);
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
     * processes the updateFields and newValues arrays into
     * a update config
     * used by: updateMultipleFieldsForCollection
     * @return mixed[] [status => bool, message => string, dataset => mixed[]
     * [fields => string[],values => mixed[], types => string[]]]
     */
    protected function updateMultipleMakeUpdateConfig(array $updateFields, array $newValues): array
    {
        $this->makeWorker();
        $updateConfig = [
            "fields" => [],
            "values" => [],
            "types" => [],
        ];
        $message = "ok";
        $all_ok = true;
        $loop = 0;
        while ($loop < count($updateFields)) {
            $field_type = $this->worker->getFieldType($updateFields[$loop], false);
            if ($field_type == null) {
                $all_ok = false;
                $message = "Unable to find fieldtype: " . $updateFields[$loop];
                break;
            }
            $updateConfig["fields"][] = $updateFields[$loop];
            $updateConfig["values"][] = $newValues[$loop];
            $updateConfig["types"][] = $this->worker->getFieldType($updateFields[$loop], true);
            $loop++;
        }
        return ["status" => $all_ok, "dataset" => $updateConfig, "message" => $message];
    }

    /**
     * updateMultipleGetUpdatedIds
     * using the fields that have changes
     * it builds an array of ids that need to have
     * the update applied to them and the total number of
     * items that need to be updated
     * @return int[] ids to change
     */
    protected function updateMultipleGetUpdatedIds(array $updateFields, array $newValues): array
    {
        $changed_ids = [];
        $ids = $this->getAllIds();
        $total_updateFields = count($updateFields);
        foreach ($ids as $entry_id) {
            $localWorker = $this->collected[$entry_id];
            $loop2 = 0;
            while ($loop2 < $total_updateFields) {
                $lookup = "_" . ucfirst($updateFields[$loop2]);
                if ($localWorker->$lookup != $newValues[$loop2]) {
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
     * apply the new values for each field to the collection
     */
    protected function updateMultipleApplyChanges(array $updateFields, array $newValues): void
    {
        $ids = $this->getAllIds();
        $total_updateFields = count($updateFields);
        foreach ($ids as $entry_id) {
            $localWorker = $this->collected[$entry_id];
            $loop2 = 0;
            while ($loop2 < $total_updateFields) {
                $applier = "_" . ucfirst($updateFields[$loop2]);
                $localWorker->$applier = $newValues[$loop2];
                $loop2++;
            }
        }
    }
    /**
     * updateMultipleFieldsForCollection
     * using the fields and values updates the collection
     * and apply the changes to the database.
     */
    public function updateMultipleFieldsForCollection(array $updateFields, array $newValues): MultiUpdateReply
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
        if (count($updateFields) <= 0) {
            $this->addError("No fields being updated!");
            return new MultiUpdateReply($this->myLastErrorBasic);
        }
        $makeUpdateConfig = $this->updateMultipleMakeUpdateConfig($updateFields, $newValues);
        if ($makeUpdateConfig["status"] == false) {
            $this->addError($makeUpdateConfig["message"]);
            return new MultiUpdateReply($this->myLastErrorBasic);
        }
        $changeConfig = $this->updateMultipleGetUpdatedIds($updateFields, $newValues);
        if (count($changeConfig) <= 0) {
            return new MultiUpdateReply("No changes made", true);
        }
        $updateConfig = $makeUpdateConfig["dataset"];
        $whereConfig = [
            "fields" => ["id"],
            "matches" => ["IN"],
            "values" => [$changeConfig],
            "types" => ["i"],
        ];
        $table = $this->worker->getTable();
        $totalChanges = count($changeConfig);
        unset($changeConfig);
        unset($makeUpdateConfig);
        $update_status = $this->sql->updateV2($table, $updateConfig, $whereConfig, $totalChanges);
        if ($update_status->status == false) {
            $this->addError("Update failed because:" . $update_status->message);
            return new MultiUpdateReply($this->myLastErrorBasic);
        }
        $this->updateMultipleApplyChanges($updateFields, $newValues);
        return new MultiUpdateReply("ok", true, $totalChanges);
    }
}
