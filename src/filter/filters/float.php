<?php

abstract class inputFilter_filter_float extends inputFilter_filter_integer
{
    protected function filter_float(string $value, array $args = []): ?float
    {
        $this->failure = false;
        $this->testOK = true;
        if (is_float($value + 0)) {
            $value = floatval($value);
            if (array_key_exists("zeroCheck", $args)) {
                if ($value == "0") {
                    $this->testOK = false;
                    $this->whyfailed = "Zero value detected";
                }
            }
            if ($this->testOK) {
                return $value;
            }
        } else {
            $this->whyfailed = "Not a float";
        }
        return null;
    }
}
