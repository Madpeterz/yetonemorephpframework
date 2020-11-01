<?php

abstract class mysqli_count extends mysqli_select
{
    public function multi_table_group_count_v2(array $tables, ?array $where_config = null): array
    {
        if ($this->sqlStart() == true) {
            if (count($tables) > 1) {
                $opens = "(";
                $closes = ")";
            // this is just a work around for atoms poor markup colors...

                $sql = " SELECT idfield AS id, count(idfield) AS fulltotal FROM " . $opens . "";
                $addon = "";
                $failed = false;
                $failed_on = "";
                $bind_text = "";
                $bind_args = [];
                foreach ($tables as $table => $group_field) {
                    $sql .= $addon;
                    $sql .= " " . $opens . " SELECT " . $group_field . " as idfield FROM " . $table . " ";
                    $sql .= " " . $closes . " ";
                    $addon = " UNION ALL";
                    if ($failed == true) {
                            break;
                    }
                }
                $sql .= " " . $closes . " tb1";
                $sql .= " GROUP BY idfield";
                $sql .= " ORDER BY fulltotal DESC";
                if ($failed == false) {
                    if ($sql != "empty_in_array") {
                        if ($stmt = $this->sqlConnection->prepare($sql)) {
                            $bind_ok = true;
                            if (count($bind_args) > 0) {
                                    $bind_ok = mysqli_stmt_bind_param($stmt, $bind_text, ...$bind_args);
                            }
                            if ($bind_ok == true) {
                                if ($stmt->execute() == true) {
                                    $result = $stmt->get_result();
                                    $dataSet = [];
                                    $loop = 0;
                                    while ($entry = $result->fetch_assoc()) {
                                        $dataSet[$entry["id"]] = $entry["fulltotal"];
                                        $loop++;
                                    }
                                    return ["status" => true, "dataset" => $dataSet ,"message" => "ok","run_sql" => $sql];
                                } else {
                                    return $this->failure("Unable to execute");
                                }
                            } else {
                                return $this->failure("Unable to bind: " . $bind_text . " " . print_r($bind_args, true) . "");
                            }
                        } else {
                            return $this->failure("Unable to prepair: " . $sql);
                        }
                    } else {
                        return ["status" => true,"dataset" => [],"message" => "No data empty IN array"];
                    }
                } else {
                    return $this->failure("failed with message:" . $failed_on);
                }
            } else {
                return $this->failure("Please use basic_count for single table counts.");
            }
        } else {
            return $this->failure("Unable to connect to database");
        }
    }
    public function group_count(string $table, string $group_field, array $wherefields = [], array $wherevalues = [], string $joinOption = "AND"): array
    {
        /*
            a more detailed count when you need a grouped result
            if you only want how many rows there are that clears the where conditions
            please use basic_count

            returns [true]: array("status"=>true,"dataset"=>array)
                    dataset entrys:
                        "field_value" (x) => "count" (X)
                        example
                            432 => 22
                            so there are 22 entrys with field value 432.
            returns [false]: array("status"=>false,"message" => "why it failed")
        */
        $this->sqlStart();
        if ($table != null) {
            if ($group_field != null) {
                $sql = "SELECT " . $group_field . ", count(*) AS \"Entrys\" FROM " . $table . " ";
                if (count($wherefields) == count($wherevalues)) {
                    if (count($wherefields) > 0) {
                        $sql = $this->bindParams($sql, $wherefields, "WHERE", $joinOption);
                    }
                    $sql .= " GROUP BY " . $group_field . " ORDER BY `Entrys` DESC";
                    if ($stmt = $this->sqlConnection->prepare($sql)) {
                        if (count($wherevalues) > 0) {
                            $this->sql_bind($stmt, $wherevalues);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $return_data = [];
                        while ($data = $result->fetch_assoc()) {
                            $return_data[$data[$group_field]] = $data["Entrys"];
                        }
                        return ["status" => true,"dataset" => $return_data];
                    } else {
                        return $this->failure("Unable to prepare. " . $sql . "");
                    }
                } else {
                    return $this->failure("Incorrect Where settings.");
                }
            } else {
                return $this->failure("Please select a groupby field");
            }
        } else {
            return $this->failure("Please select a table first");
        }
    }
    public function basic_count_v2(string $table, array $whereconfig = []): array
    {
        $this->sqlStart();
        if ($table != null) {
            $load_data = $this->selectV2([
                    "table" => $table,
                    "fields" => ["COUNT(*) AS sqlCount"]
                ], null, $whereconfig);
            if ($load_data["status"] == true) {
                if (count($load_data["dataSet"]) > 0) {
                    return ["status" => true, "count" => $load_data["dataSet"][0]["sqlCount"],"message" => "ok"];
                } else {
                    return ["status" => false,"count" => 0,"message" => $load_data["message"]];
                }
            } else {
                return ["status" => false,"count" => 0,"message" => $load_data["message"]];
            }
        } else {
            return $this->failure("No table selected to count from");
        }
    }
}
