<?php

abstract class inputFilter_filter_string extends inputFilter_filter_array
{
    protected function filter_string(string $value, array $args = []): ?string
    {
        $this->failure = false;
        $this->testOK = true;
        if ((array_key_exists("maxLength", $args) == true) && (array_key_exists("minLength", $args)  == true)) {
            if ($args["minLength"] > $args["maxLength"]) {
                $this->whyfailed = "Length values are mixed up";
                $this->testOK = false;
            }
        }
        if ($this->testOK) {
            if (array_key_exists("minLength", $args) == true) {
                if (strlen($value) < $args["minLength"]) {
                    $this->whyfailed = "Failed min length check";
                    $this->testOK = false;
                }
            }
            if (array_key_exists("maxLength", $args) == true) {
                if (strlen($value) > $args["maxLength"]) {
                    $this->whyfailed = "Failed max length check";
                    $this->testOK = false;
                }
            }
        }
        if ($this->testOK) {
            return $value;
        }
        return null;
    }
}
