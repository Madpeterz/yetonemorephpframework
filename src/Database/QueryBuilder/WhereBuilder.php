<?php

namespace YAPF\Framework\Database\QueryBuilder;

use YAPF\Framework\Database\QueryBuilder\Enums\WhereCondition;

class WhereBuilder extends MysqliTypecode
{
    protected array $whereSteps = [];
    protected array $whereValues = [];
    protected string $whereTypecode = "";
    public function startsWith(string $fieldname, string $value): self
    {
        return $this->condition($fieldname, WhereCondition::STARTS_WITH, $value);
    }
    public function endsWith(string $fieldname, string $value): self
    {
        return $this->condition($fieldname, WhereCondition::ENDS_WITH, $value);
    }
    public function contains(string $fieldname, string $value): self
    {
        return $this->condition($fieldname, WhereCondition::CONTAINS, $value);
    }
    public function inList(string $fieldname, array $list): self
    {
        return $this->condition($fieldname, WhereCondition::IN_LIST, $list);
    }
    public function notInList(string $fieldname, array $list): self
    {
        return $this->condition($fieldname, WhereCondition::NOT_IN, $list);
    }
    public function lessThan(string $fieldname, int|float $value, bool $allowEqualTo = true): self
    {
        if ($allowEqualTo == true) {
            return $this->condition($fieldname, WhereCondition::EQ_OR_LESSTHAN, $value);
        }
        return $this->condition($fieldname, WhereCondition::LESS_THAN, $value);
    }
    public function greaterThan(string $fieldname, int|float $value, bool $allowEqualTo = true): self
    {
        if ($allowEqualTo == true) {
            return $this->condition($fieldname, WhereCondition::EQ_OR_MORETHAN, $value);
        }
        return $this->condition($fieldname, WhereCondition::MORE_THAN, $value);
    }
    public function is(string $fieldname, $value): self
    {
        if ($value == null) {
            return $this->isNotNull($fieldname);
        }
        return $this->condition($fieldname, WhereCondition::MATCHS, $value);
    }
    public function isNot(string $fieldname, $value): self
    {
        if ($value == null) {
            return $this->isNull($fieldname);
        }
        return $this->condition($fieldname, WhereCondition::DOES_NOT_MATCH, $value);
    }
    protected function isNull(string $fieldname): self
    {
        return $this->condition($fieldname, WhereCondition::IS, null);
    }
    protected function isNotNull(string $fieldname): self
    {
        return  $this->condition($fieldname, WhereCondition::IS_NOT, null);
    }
    public function and(): self
    {
        $this->whereSteps[] = "AND";
        return $this;
    }
    public function or(): self
    {
        $this->whereSteps[] = "OR";
        return $this;
    }
    public function startGroup(): self
    {
        $this->whereSteps[] = "(";
        return $this;
    }
    public function endGroup(): self
    {
        $this->whereSteps[] = ")";
        return $this;
    }
    protected function condition(string $fieldname, WhereCondition $type, $value): self
    {
        if (is_array($value) == false) {
            $adding = $fieldname . " " . $type->value;
            $valueadd = "NULL";
            if ($value === null) {
                $this->whereValues[] = $value;
                $valueadd = "?";
                $this->whereTypecode .= $this->valueType($value);
            }
            $this->whereSteps[] = $adding . " " . $valueadd;
            return $this;
        }
        if (count($value) > 0) {
            $adding = $fieldname . " " . $type->value . "(";
            $addon = "";
            foreach ($value as $bit) {
                if ($bit === null) {
                    continue;
                }
                $adding .= $addon . "?";
                $addon = ",";
                $this->whereValues[] = $bit;
                $this->whereTypecode .= $this->valueType($bit);
            }
            $whereSteps[] = " " . $adding . ")";
        }
        return $this;
    }
    public function asJson(): string
    {
        return json_encode($this->whereSteps);
    }
    public function asSql(): string
    {
        $sql = "";
        foreach ($this->whereSteps as $step) {
            $sql .= " " . $step;
        }
        return $sql;
    }
    public function reset(): self
    {
        $this->whereSteps = [];
        $this->whereValues = [];
        $this->whereTypecode = "";
        return $this;
    }
}
