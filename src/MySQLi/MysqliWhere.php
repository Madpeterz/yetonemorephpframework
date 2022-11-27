<?php

namespace YAPF\Framework\MySQLi;

use Exception;

abstract class MysqliWhere extends MysqliFunctions
{
    protected function checkProcessWhere(
        ?array $whereConfig,
        string &$failedWhy,
    ): bool {
        if ($whereConfig === null) {
            $failedWhy = "note: Where config is null skipping";
            return true;
        } elseif (count($whereConfig) == 0) {
            $failedWhy = "whereConfig is empty but not null!";
            return false;
        }
        $check_keys = ["fields","values","types","matches"];
        $missing_keys = [];
        foreach ($check_keys as $test_key) {
            if (array_key_exists($test_key, $whereConfig) == false) {
                $missing_keys[] = $test_key;
            }
        }
        if (count($missing_keys) > 0) {
            $failedWhy = "missing where keys:" . implode(",", $missing_keys);
            return false;
        } elseif (count($whereConfig["fields"]) != count($whereConfig["values"])) {
            $failedWhy = "count error fields <=> values";
            return false;
        } elseif (count($whereConfig["values"]) != count($whereConfig["types"])) {
            $failedWhy = "count error values <=> types";
            return false;
        } elseif (count($whereConfig["types"]) != count($whereConfig["matches"])) {
            $failedWhy = "count error types <=> matches";
            return false;
        } elseif (count($whereConfig["fields"]) == 0) {
            $failedWhy = "Note: where config keys are empty inside  skipping";
            return true;
        }

        $loop = 0;
        foreach ($whereConfig["types"] as $t) {
            if (in_array($t, ["s","d","i"]) == false) {
                $failedWhy = "index: " . $loop . " is not as we expect: " . json_encode($whereConfig);
                throw new Exception($failedWhy, 911);
                return false; // this should never run but for that 0.00000001%
            }
            $loop++;
        }
        return true;
    }
    /**
     * processWhere
     * processes the whereConfig to make
     * sure its valid and setup fully before passing
     * it over to the builder
     * returns the result of the builder if all is ok
     * returns false if something failed and is on fire
     */
    protected function processWhere(
        string &$sql,
        ?array $whereConfig,
        string &$bindText,
        array &$bindArgs,
        string &$failedWhy,
        bool &$failed
    ): bool {
        $reply = $this->checkProcessWhere($whereConfig, $failedWhy);
        if ($reply == false) {
            return false;
        }
        if ($failedWhy != "") {
            return true;
        }
        if (array_key_exists("joinWith", $whereConfig) == false) {
            $whereConfig["joinWith"] = "AND";
        }
        if (is_array($whereConfig["joinWith"]) == false) {
            $new_array = [];
            $loop = 1;
            $total = count($whereConfig["types"]);
            while ($loop < $total) {
                $new_array[] = $whereConfig["joinWith"];
                $loop++;
            }
            $whereConfig["joinWith"] = $new_array;
        }
        if (count($whereConfig["joinWith"]) != (count($whereConfig["types"]) - 1)) {
            $failedWhy = "whereConfig joinWith count error";
            return false;
        }

        if (array_key_exists("asFunction", $whereConfig) == false) {
            $whereConfig["asFunction"] = [];
        }

        while (count($whereConfig["asFunction"]) < count($whereConfig["fields"])) {
            $whereConfig["asFunction"][] = 0;
        }

        $failedWhy = "Passed";
        $this->buildWhere($sql, $bindText, $bindArgs, $whereConfig, $failed, $failedWhy);
        if ($failed == true) {
            return false;
        }
        return true;
    }
    /**
     * buildWhereCaseIs
     * used for IS and IS NOT match cases
     * if the match is not one of these nothing happens.
     */
    protected function buildWhereCaseIs(string &$whereCode, string $field, string $match): void
    {
        $whereCode .= "" . $field . " " . $match . " null ";
    }
    /**
     * buildWhereCaseLike
     * used for LIKE where cases
     * using the magic of string replacement
     * accepts % LIKE, LIKE %, % LIKE % and LIKE
     * any other match type is skipped.
     */
    protected function buildWhereCaseLike(
        string &$whereCode,
        string $field,
        string $match,
        string &$bindText,
        array &$bindArgs,
        $value,
        string $type
    ): void {
        $adj = str_replace(" ", "", $match);
        $value = str_replace("LIKE", $value, $adj);
        $match = "LIKE";
        $whereCode .= "" . $field . " " . $match . " ? ";
        $bindText .= $type;
        $bindArgs[] = $value;
    }
    /**
     * buildWhereCaseIn
     * used for IN and NOT IN where cases
     * any other match type is skipped.
     */
    protected function buildWhereCaseIn(
        string &$whereCode,
        string $field,
        string $match,
        string &$bindText,
        array &$bindArgs,
        $value,
        string $type,
        string &$sql
    ): void {
        if (is_array($value) == false) {
            $sql = "empty_in_array";
            return;
        }
        if (count($value) == 0) {
            $sql = "empty_in_array";
            return;
        }
        $whereCode .=  $field . " " . $match . " (";
        $addon2 = "";
        foreach ($value as $entry) {
            $whereCode .= $addon2 . " ? ";
            $addon2 = ", ";
            $bindText .= $type;
            $bindArgs[] = $entry;
        }
        $whereCode .= ") ";
    }
    /**
     * whereCaseProcessor
     * redirects the builder to the correct
     * where case.
     */
    protected function whereCaseProcessor(
        string &$whereCode,
        string $field,
        ?string $match,
        string &$bindText,
        array &$bindArgs,
        $value,
        string $type,
        int $asFunction,
        string &$sql,
        bool &$failed,
        string &$failedWhy
    ): void {
        $matchTypes = [
        "=",
        "<=",
        ">=",
        "!=",
        "<",
        ">",
        "IS",
        "IS NOT",
        "% LIKE",
        "LIKE %",
        "% LIKE %",
        "IN",
        "NOT IN",
        ];
        if (in_array($match, $matchTypes) == false) {
            $failed = true;
            $failedWhy = "Unsupported where match type!";
            return;
        }
        if (in_array($match, ["IS","IS NOT"]) == true) {
            $this->buildWhereCaseIs($whereCode, $field, $match);
            return;
        } elseif (in_array($match, ["% LIKE","LIKE %","% LIKE %"]) == true) {
            $this->buildWhereCaseLike($whereCode, $field, $match, $bindText, $bindArgs, $value, $type);
            return;
        } elseif (in_array($match, ["IN","NOT IN"]) == true) {
            $this->buildWhereCaseIn($whereCode, $field, $match, $bindText, $bindArgs, $value, $type, $sql);
            return;
        }
        $whereString = "`" . $field . "` " . $match . " ?";
        if ($asFunction == 1) {
            $whereString = $field . " " . $match . " ?";
        }
        $whereCode .= $whereString;
        $bindText .= $type;
        $bindArgs[] = $this->convertIfBool($value);
    }
    protected function whereJoinBuilder(
        string &$sql,
        string &$bindText,
        array &$bindArgs,
        array $whereConfig,
        bool &$failed,
        string &$failedWhy,
        string &$whereCode
    ): void {
        $this->openGroups = 1;
        $whereCode .= "(";
        $endGroupAfter = [") AND",") OR"];
        $startGroupBefore = ["( AND","( OR"];
        $loop = 0;
        while ($loop < count($whereConfig["fields"])) {
            $this->whereCaseWriter(
                $whereConfig,
                $loop,
                $whereCode,
                $bindText,
                $bindArgs,
                $sql,
                $failed,
                $failedWhy,
                $this->openGroups
            );
            if ($failed == true) {
                break;
            }
            if (array_key_exists($loop, $whereConfig["joinWith"]) == true) {
                if (in_array($whereConfig["joinWith"][$loop], $startGroupBefore) == true) {
                    $this->openGroups++;
                    $whereCode .= "(";
                }
                if (in_array($whereConfig["joinWith"][$loop], $endGroupAfter) == true) {
                    $this->pending_closer = 1;
                }
            }
            if ($sql == "empty_in_array") {
                break;
            }
            $loop++;
        }
        while ($this->openGroups > 0) {
            $whereCode .= ")";
            $this->openGroups--;
        }
    }

    protected function helperArrayElementInArray(array $sourceElements, array $testIn): bool
    {
        foreach ($sourceElements as $entry) {
            if (in_array($entry, $testIn) == true) {
                return true;
            }
        }
        return false;
    }

    protected int $pending_closer = 0;
    protected int $openGroups = 0;
    protected function whereCaseWriter(
        array $whereConfig,
        int $loop,
        string &$whereCode,
        string &$bindText,
        array &$bindArgs,
        string &$sql,
        bool &$failed,
        string &$failedWhy
    ): void {
        $value = $whereConfig["values"][$loop];
        if ($value === "null") {
            $value = null;
        }
        $field = $whereConfig["fields"][$loop];
        $this->whereCaseProcessor(
            $whereCode,
            $field,
            $whereConfig["matches"][$loop],
            $bindText,
            $bindArgs,
            $value,
            $whereConfig["types"][$loop],
            $whereConfig["asFunction"][$loop],
            $sql,
            $failed,
            $failedWhy
        );
        if ($failed == true) {
            return;
        }
        if ($this->pending_closer == 1) {
            $this->pending_closer = 0;
            $this->openGroups--;
            $whereCode .= ")";
        }
        if ($sql != "empty_in_array") {
            if (count($whereConfig["joinWith"]) > $loop) {
                $whereCode .= " ";
                $whereCode .= strtr($whereConfig["joinWith"][$loop], ["( " => "",") " => ""]);
                $whereCode .= " ";
            }
        }
    }
    protected function complexBuildWhere(
        string &$sql,
        string &$bindText,
        array &$bindArgs,
        array $whereConfig,
        bool &$failed,
        string &$failedWhy
    ): void {
        $whereCode = "";
        $this->whereJoinBuilder(
            $sql,
            $bindText,
            $bindArgs,
            $whereConfig,
            $failed,
            $failedWhy,
            $whereCode
        );
        if ($sql != "empty_in_array") {
            if ($whereCode != "") {
                $whereCode = trim($whereCode);
                $sql .= " WHERE " . $whereCode;
            }
        }
    }
    protected function simpleBuildWhere(
        string &$sql,
        string &$bindText,
        array &$bindArgs,
        array $whereConfig,
        bool &$failed,
        string &$failedWhy
    ): void {
        $whereCode = "";
        $loop = 0;
        $this->openGroups = 0;
        $this->pending_closer = 0;
        while ($loop < count($whereConfig["fields"])) {
            $this->whereCaseWriter(
                $whereConfig,
                $loop,
                $whereCode,
                $bindText,
                $bindArgs,
                $sql,
                $failed,
                $failedWhy
            );
            if ($failed == true) {
                break;
            }
            if ($sql == "empty_in_array") {
                break;
            }
            $loop++;
        }
        if ($sql != "empty_in_array") {
            if ($whereCode != "") {
                $whereCode = trim($whereCode);
                $sql .= " WHERE " . $whereCode;
            }
        }
    }
    /**
     * buildWhere
     * oh lord, he coming,
     * builds the where statement using the
     * settings past to it.
     */
    protected function buildWhere(
        string &$sql,
        string &$bindText,
        array &$bindArgs,
        array $whereConfig,
        bool &$failed,
        string &$failedWhy
    ): void {
        if ($this->helperArrayElementInArray(["( AND", "( OR",") AND", ") OR"], $whereConfig["joinWith"]) == true) {
            $this->complexBuildWhere($sql, $bindText, $bindArgs, $whereConfig, $failed, $failedWhy);
            return;
        }
        $this->simpleBuildWhere($sql, $bindText, $bindArgs, $whereConfig, $failed, $failedWhy);
    }
}
