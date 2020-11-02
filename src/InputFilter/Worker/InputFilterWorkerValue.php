<?php

namespace YAPF\InputFilter\Worker;

abstract class InputFilterWorkerValue extends InputFilterTypeColor
{
    public function valueFilter($value = null, string $filter, array $args = [])
    {
        $this->failure = false;
        if ($value != null) {
            if (is_object($value) == true) {
                $this->failure = true;
                $this->whyfailed = "InputFilter can not deal with objects you crazy person";
                return null;
            } elseif (is_string($value) == true) {
                $this->testOK = true;
                $fast_test_numbers = ["integer","double","float"];
                if (in_array($filter, $fast_test_numbers) == true) {
                    if (is_numeric($value) == false) {
                        $this->failure = true;
                        $this->testOK = false;
                        $this->whyfailed = "Expects value to be numeric but its not";
                    }
                }
                if ($this->testOK == true) {
                    if ($filter == "string") {
                        $value = $this->filter_string($value, $args);
                    } elseif ($filter == "integer") {
                        $value = $this->filter_integer($value, $args);
                    } elseif (($filter == "double") || ($filter == "float")) {
                        $value = $this->filter_float($value, $args);
                    } elseif ($filter == "checkbox") {
                        $value = $this->filter_checkbox($value, $args);
                    } elseif ($filter == "bool") {
                        $value = $this->filter_bool($value, $args);
                    } elseif (($filter == "uuid") || ($filter == "key")) {
                        $value = $this->filter_uuid($value, $args);
                    } elseif ($filter == "vector") {
                        $value = $this->filter_vector($value, $args);
                    } elseif ($filter == "date") {
                        $value = $this->filter_date($value, $args);
                    } elseif ($filter == "email") {
                        $value = $this->filter_email($value, $args);
                    } elseif ($filter == "url") {
                        $value = $this->filter_url($value, $args);
                    } elseif ($filter == "color") {
                        $value = $this->filter_color($value, $args);
                    } elseif ($filter == "trueFalse") {
                        $value = $this->filter_trueFalse($value);
                    } elseif ($filter == "json") {
                        $value = $this->filter_json($value);
                    }
                    if ($value !== null) {
                        return $value;
                    }
                    $this->failure = true;
                }
            } else {
                if ($filter == "array") {
                            return $this->filter_array($value, $args);
                } else {
                        $value = null;
                        $this->whyfailed = "Type error expected a string but got somthing else";
                }
            }
        } else {
            $this->failure = true;
        }
        if ($this->failure == true) {
            if ($filter == "checkbox") {
                return 0;
            } elseif ($filter == "trueFalse") {
                return 0;
            }
        }
        return null;
    }
    public function varFilter($currentvalue, string $filter = "string", array $args = [])
    {
        return $this->valueFilter($currentvalue, $filter, $args);
    }
}
