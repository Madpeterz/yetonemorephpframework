<?php

namespace YAPF\InputFilter\FilterTypes;

use YAPF\Worker\InputFilterWorkerBase as InputFilterBase;

abstract class InputFilterTypeArray extends InputFilterWorkerBase
{
    protected function filter_array($value, array $args = []): ?array
    {
        // used by groupped inputs
        if (is_array($value) == true) {
            return $value;
        } else {
            $this->whyfailed = "not an array";
            return null;
        }
    }
}
