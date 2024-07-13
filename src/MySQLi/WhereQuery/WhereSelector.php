<?php

namespace YAPF\Framework\MySQLi\WhereQuery;

class WhereSelector
{
    protected ?string $valueSource = null;
    protected string $valueMode = "s";
    // strings
    protected ?string $valueString = null;
    // ints
    protected ?int $valueInt = null;
    // double
    protected ?float $valueFloat = null;
    // arrays
    protected array $arrayValues = [];

    protected bool $enabled = false;
    protected string $matcher = "";
    public function greaterThanEqualTo($value): bool
    {
        return $this->equalToProcess($value, ">=");
    }
    public function greaterThan($value): bool
    {
        return $this->equalToProcess($value, ">");
    }
    public function lessThanEqualTo($value): bool
    {
        return $this->equalToProcess($value, "<=");
    }
    public function lessThan($value): bool
    {
        return $this->equalToProcess($value, "<");
    }
    public function notEqualTo($value): bool
    {
        return $this->equalToProcess($value, "!=");
    }
    public function equalTo($value): bool
    {
        return $this->equalToProcess($value, "=");
    }
    protected function equalToProcess($value, string $matcher): bool
    {
        if (is_object($value) == true) {
            return false;
        }
        $this->enabled = true;
        $this->matcher = $matcher;
        $this->valueInt = intval($value);
        $this->valueFloat = floatval($value);
        $this->valueString = (string)$value;
        if ((is_string($value) == true) || (is_null($value) == true)) {
            return $this->equalsString($value);
        } elseif ((is_bool($value) == true) || (is_int($value) == true)) {
            return $this->equalsInt($value);
        } elseif ((is_float($value) == true) || (is_double($value) == true)) {
            return $this->equalsfloat($value);
        } elseif (is_array($value) == true) {
            return $this->inList($value);
        }
        return false;
    }
    protected function equalsString(?string $value): bool
    {
        if ($value === null) {
            return $this->isNull();
        }
        $this->valueSource = "valueString";
        $this->valueMode = "s";
        return true;
    }
    protected function equalsfloat(?int $value): bool
    {
        if ($value === null) {
            return $this->isNull();
        }
        $this->valueSource = "valueFloat";
        $this->valueMode = "d";
        return true;
    }
    protected function equalsInt(?float $value): bool
    {
        if ($value === null) {
            return $this->isNull();
        }
        $this->valueSource = "valueInt";
        $this->valueMode = "i";
        return true;
    }
    protected function isNull(): bool
    {
        $this->enabled = true;
        $this->matcher = ($this->matcher == "=") ? "IS" : "IS NOT";
        $this->valueString = "NULL";
        $this->valueSource = "valueString";
        $this->valueMode = "s";
        return true;
    }
    protected function inList(?array $values): bool
    {
        if (is_array($values) == false) {
            return false;
        }
        if (count($values) == 0) {
            return false;
        }
        $this->enabled = true;
        $this->matcher = ($this->matcher == "=") ? "IN" : "NOT IN";
        $this->arrayValues = $values;
        $this->valueSource = "arrayValues";
        return true;
    }
}
