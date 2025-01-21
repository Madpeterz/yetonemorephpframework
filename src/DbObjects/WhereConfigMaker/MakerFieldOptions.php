<?php

namespace YAPF\Framework\DbObjects\WhereConfigMaker;

use Exception;
use PhpParser\Node\Expr\Cast\Double;

class MakerFieldOptions
{
    public function __construct(protected WhereConfigMaker $master)
    {
        
    }
    protected function addEntry($value, string $op="=", ?string $forceTypecoede=null): MakerJoinOptions
    {
        $matchType = $forceTypecoede;
        if($matchType === null)
        {
            $matchType = $this->valueType($value);
        }
        $this->master->whereConfig["values"][] = $value;
        $this->master->whereConfig["types"][] = $matchType;
        $this->master->whereConfig["matches"][] = $op;
        return new MakerJoinOptions($this->master);
    }
    protected function valueType($value): string
    {
        if(is_array($value) == true) {
            if(count($value) == 0)
            {
                throw new Exception("options array is empty");
            }
            $value = $value[0];
        }
        if(is_string($value) == true) return "s";
        else if(is_int($value) == true) return "i";
        else if(is_float($value) == true) return "d";
        else if(is_double($value) == true) return "d";
        else if(is_bool($value) == true) return "i";
        else if(is_long($value) == true) return "i";
        throw new Exception("Unknown value type");
        return "s";
    }
    public function isNull(): MakerJoinOptions
    {
        return $this->addEntry(NULL,"IS","i");
    }
    public function isNotNull(): MakerJoinOptions
    {
        return $this->addEntry(NULL,"IS NOT","i");
    }
    public function in(array $options): MakerJoinOptions
    {
        return $this->addEntry($options,"IN");
    }
    public function notIn(array $options): MakerJoinOptions
    {
        return $this->addEntry($options,"NOT IN");
    }
    public function greaterThan(int|float $value): MakerJoinOptions
    {
        return $this->addEntry($value,">");
    }
    public function lessThan(int|float $value): MakerJoinOptions
    {
        return $this->addEntry($value,"<");
    }
    public function greaterThanEqualTo(int|float $value): MakerJoinOptions
    {
        return $this->addEntry($value,">=");
    }
    public function lessThanThanEqualTo(int|float $value): MakerJoinOptions
    {
        return $this->addEntry($value,"<=");
    }
    public function equalTo(int|float|string $value): MakerJoinOptions
    {
        return $this->addEntry($value,"=");
    }
    public function notEqualTo(int|float|string $value): MakerJoinOptions
    {
        return $this->addEntry($value,"!=");
    }
}