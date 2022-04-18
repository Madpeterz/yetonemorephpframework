<?php

namespace YAPF\Framework\MySQLi;

use YAPF\Framework\Responses\MySQLi\CountReply;
use YAPF\Framework\Responses\MySQLi\SelectReply;

class MysqliEnabled extends MysqliQuery
{
        /**
     * basicCountV2
     * $whereConfig: see selectV2.readme
     * Note: if your table does not have an id field
     * this function will not give the results you expect
     */
    public function basicCountV2(string $table, array $whereConfig = null): CountReply
    {
        if (strlen($table) == 0) {
            $this->addError("No table given");
            return new CountReply($this->myLastErrorBasic);
        }
        $basic_config = [
            "table" => $table,
            "fields" => ["COUNT(id) AS sqlCount"],
        ];
        $loadData = $this->selectV2($basic_config, null, $whereConfig);
        if ($loadData->status == false) {
            $this->addError($loadData->message);
            return new CountReply($this->myLastErrorBasic);
        }
        return new CountReply("ok", true, $loadData->dataset[0]["sqlCount"]);
    }
    /**
     * GroupCountV2
     * $whereConfig: see selectV2.readme
     * Note: if your table does not have an id field
     * this function will not give the results you expect
     */
    public function groupCountV2(string $table, string $groupOnField, array $whereConfig = null): SelectReply
    {
        if (strlen($table) == 0) {
            $this->addError("No table selected");
            return new SelectReply($this->myLastErrorBasic);
        }
        if (strlen($groupOnField) == 0) {
            $this->addError("No group field given");
            return new SelectReply($this->myLastErrorBasic);
        }
        $basic_config = [
            "table" => $table,
            "fields" => [$groupOnField,"COUNT(id) AS items"],
        ];
        $options_config = [
            "groupBy" => $groupOnField,
        ];
        return $this->selectV2($basic_config, null, $whereConfig, $options_config);
    }
}
