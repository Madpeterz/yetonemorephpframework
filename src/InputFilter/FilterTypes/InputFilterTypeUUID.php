<?php

namespace YAPF\InputFilter\FilterTypes;

abstract class InputFilterTypeUUID extends InputFilterTypeBool
{
    protected function filter_uuid(string $value, array $args = []): ?string
    {
        $this->failure = false;
        $this->testOK = true;
        if (preg_match('/^[0-9A-Fa-f]{8}\-[0-9A-Fa-f]{4}\-4[0-9A-Fa-f]{3}\-[89ABab][0-9A-Fa-f]{3}\-[0-9A-Fa-f]{12}$/i', $value)) {
            return $value;
        } elseif (preg_match('/^[0-9A-Fa-f]{8}\-[0-9A-Fa-f]{4}\-[0-9A-Fa-f]{4}\-[0-9A-Fa-f]{4}\-[0-9A-Fa-f]{12}$/i', $value)) {
            return $value;
        }
        return null;
    }
}
