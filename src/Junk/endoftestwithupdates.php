<?php

namespace YAPF\Junk;

use YAPF\DbObjects\GenClass\GenClass as GenClass;

// Do not edit this file, rerun gen.php to update!
class Endoftestwithupdates extends genClass
{
    protected $use_table = "endoftestwithupdates";
    protected $dataset = [
        "id" => ["type" => "int", "value" => null],
        "username" => ["type" => "str", "value" => null],
        "oldusername" => ["type" => "str", "value" => null],
        "banned" => ["type" => "bool", "value" => 0],
    ];
    public function getUsername(): ?string
    {
        return $this->getField("username");
    }
    public function getOldusername(): ?string
    {
        return $this->getField("oldusername");
    }
    public function getBanned(): ?bool
    {
        return $this->getField("banned");
    }
    /**
    * setUsername
    * @return mixed[] [status =>  bool, message =>  string]
    */
    public function setUsername(?string $newvalue): array
    {
        return $this->updateField("username", $newvalue);
    }
    /**
    * setOldusername
    * @return mixed[] [status =>  bool, message =>  string]
    */
    public function setOldusername(?string $newvalue): array
    {
        return $this->updateField("oldusername", $newvalue);
    }
    /**
    * setBanned
    * @return mixed[] [status =>  bool, message =>  string]
    */
    public function setBanned(?bool $newvalue): array
    {
        return $this->updateField("banned", $newvalue);
    }
}
// please do not edit this file
