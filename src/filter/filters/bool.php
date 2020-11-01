<?php

abstract class inputFilter_filter_bool extends inputFilter_filter_float
{
    protected function filter_bool(string $value, array $args = []): bool
    {
        $this->failure = false;
        $this->testOK = true;
        return in_array($value, ["true",true,1,"yes","True",true,"TRUE"]);
    }
    protected function filter_trueFalse(string $value, array $args = []): int
    {
        $value = filter_bool($value);
        if ($value === true) {
            return 1;
        }
        return 0;
    }
}
