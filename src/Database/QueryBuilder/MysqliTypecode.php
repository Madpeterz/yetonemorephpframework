<?php

namespace YAPF\Framework\Database\QueryBuilder;

use YAPF\Core\ErrorControl\ErrorLogging;

class MysqliTypecode extends ErrorLogging
{
    protected function valueType($value): string
    {
        if (is_float($value) == true) {
            return "d";
        } elseif (is_int($value) == true) {
            return "i";
        }
        return "s";
    }
}
