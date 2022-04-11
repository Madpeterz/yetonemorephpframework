<?php

namespace YAPF\Framework\MySQLi;

use YAPF\Framework\Responses\MySQLi\CountReply;
use YAPF\Framework\Responses\MySQLi\SelectReply;

class MysqliEnabled extends MysqliQuery
{
        /**
     * basicCountV2
     * $where_config: see selectV2.readme
     * Note: if your table does not have an id field
     * this function will not give the results you expect
     */
    public function basicCountV2(string $table, array $whereconfig = null): CountReply
    {
        if (strlen($table) == 0) {
            $this->addError("No table given");
            return new CountReply($this->myLastErrorBasic);
        }
        $basic_config = [
            "table" => $table,
            "fields" => ["COUNT(id) AS sqlCount"],
        ];
        $load_data = $this->selectV2($basic_config, null, $whereconfig);
        if ($load_data->status == false) {
            $this->addError($load_data->message);
            return new CountReply($this->myLastErrorBasic);
        }
        return new CountReply("ok", true, $load_data->dataset[0]["sqlCount"]);
    }
    /**
     * GroupCountV2
     * $where_config: see selectV2.readme
     * Note: if your table does not have an id field
     * this function will not give the results you expect
     * dataset is formated field, entrys
     */
    public function groupCountV2(string $table, string $grouponfield, array $whereconfig = null): SelectReply
    {
        if (strlen($table) == 0) {
            $this->addError("No table selected");
            return new SelectReply($this->myLastErrorBasic);
        }
        if (strlen($grouponfield) == 0) {
            $this->addError("No group field given");
            return new SelectReply($this->myLastErrorBasic);
        }
        $basic_config = [
            "table" => $table,
            "fields" => [$grouponfield,"COUNT(id) AS Entrys"],
        ];
        $options_config = [
            "groupby" => $grouponfield,
        ];
        return $this->selectV2($basic_config, null, $whereconfig, $options_config);
    }
}
