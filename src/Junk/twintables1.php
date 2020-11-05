<?php

namespace YAPF\Junk;

use YAPF\DbObjects\GenClass\GenClass as GenClass;

// Do not edit this file, rerun gen.php to update!
class Twintables1 extends genClass
{
    protected $use_table = "twintables1";
    protected $dataset = [
        "id" => ["type" => "int", "value" => null],
        "title" => ["type" => "str", "value" => null],
        "message" => ["type" => "str", "value" => null],
    ];
    public function getTitle(): ?string
    {
        return $this->getField("title");
    }
    public function getMessage(): ?string
    {
        return $this->getField("message");
    }
    public function setTitle(?string $newvalue): array
    {
        return $this->updateField("title", $newvalue);
    }
    public function setMessage(?string $newvalue): array
    {
        return $this->updateField("message", $newvalue);
    }
}
// please do not edit this file
