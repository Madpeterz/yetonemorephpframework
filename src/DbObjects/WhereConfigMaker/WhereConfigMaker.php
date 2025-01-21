<?php

namespace YAPF\Framework\DbObjects\WhereConfigMaker;

class WhereConfigMaker
{
    protected int $openGroupId = -1;
    public array $whereConfig = [];
    protected bool $setup = false;
    public function field(string $fieldName, ?string $tableid=null): MakerFieldOptions
    {
        if($this->setup == false)
        {
            $this->setup = true;
            $this->whereConfig = [
                "fields" => [],
                "values" => [],
                "types" => [],
                "matches" => [],
                "joinWith" => [],
            ];
        }
        $entry = $fieldName;
        if($tableid != null)
        {
            $entry = $tableid.".".$entry;
        }
        $this->whereConfig["fields"][] = $entry;
        return new MakerFieldOptions($this,$fieldName);
        
    }
}