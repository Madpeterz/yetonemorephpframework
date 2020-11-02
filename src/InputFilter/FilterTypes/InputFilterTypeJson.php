<?php

namespace YAPF\InputFilter\FilterTypes;

abstract class InputFilterTypeJson extends InputFilterTypeUUID
{
    protected function filter_json(string $value): ?string
    {
        $json = json_decode($value, true);
        if (($json === false) || ($json === null)) {
            return null;
        } else {
            return $json;
        }
    }
}
