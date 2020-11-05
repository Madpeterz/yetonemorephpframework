<?php

namespace JUNK;

use YAPF\DbObjects\GenClass\GenClass as GenClass;

// Do not edit this file, rerun gen.php to update!
class Relationtestinga extends genClass
{
    protected $use_table = "relationtestinga";
    protected $dataset = [
        "id" => ["type" => "int", "value" => null],
        "name" => ["type" => "str", "value" => null],
        "linkid" => ["type" => "int", "value" => null],
    ];
    public function getName(): ?string
    {
        return $this->getField("name");
    }
    public function getLinkid(): ?int
    {
        return $this->getField("linkid");
    }
    public function setName(?string $newvalue): array
    {
        return $this->updateField("name", $newvalue);
    }
    public function setLinkid(?int $newvalue): array
    {
        return $this->updateField("linkid", $newvalue);
    }
}
// please do not edit this file
