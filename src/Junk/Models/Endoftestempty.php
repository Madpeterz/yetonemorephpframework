<?php

namespace YAPF\Junk\Models;

use YAPF\DbObjects\GenClass\GenClass as GenClass;

// Do not edit this file, rerun gen.php to update!
class Endoftestempty extends genClass
{
    protected $use_table = "test.endoftestempty";
    // Data Design
    protected $dataset = [
        "id" => ["type" => "int", "value" => null],
        "name" => ["type" => "str", "value" => null],
        "value" => ["type" => "bool", "value" => 0],
    ];
    // Getters
    public function getName(): ?string
    {
        return $this->getField("name");
    }
    public function getValue(): ?bool
    {
        return $this->getField("value");
    }
    // Setters
    /**
    * setName
    * @return mixed[] [status =>  bool, message =>  string]
    */
    public function setName(?string $newvalue): array
    {
        return $this->updateField("name", $newvalue);
    }
    /**
    * setValue
    * @return mixed[] [status =>  bool, message =>  string]
    */
    public function setValue(?bool $newvalue): array
    {
        return $this->updateField("value", $newvalue);
    }
    // Loaders
    public function loadByName(string $name): bool
    {
        return $this->loadByField("name", $name);
    }
    public function loadByValue(bool $value): bool
    {
        return $this->loadByField("value", $value);
    }
}
// please do not edit this file
