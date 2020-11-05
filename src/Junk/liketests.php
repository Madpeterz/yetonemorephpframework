<?php

namespace YAPF\Junk;

use YAPF\DbObjects\GenClass\GenClass as GenClass;

// Do not edit this file, rerun gen.php to update!
class Liketests extends genClass
{
    protected $use_table = "liketests";
    protected $dataset = [
        "id" => ["type" => "int", "value" => null],
        "name" => ["type" => "str", "value" => null],
        "value" => ["type" => "str", "value" => null],
    ];
    public function getName(): ?string
    {
        return $this->getField("name");
    }
    public function getValue(): ?string
    {
        return $this->getField("value");
    }
    public function setName(?string $newvalue): array
    {
        return $this->updateField("name", $newvalue);
    }
    public function setValue(?string $newvalue): array
    {
        return $this->updateField("value", $newvalue);
    }
}
// please do not edit this file
