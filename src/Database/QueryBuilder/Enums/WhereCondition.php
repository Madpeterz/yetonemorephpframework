<?php

namespace YAPF\Framework\Database\QueryBuilder\Enums;

enum WhereCondition: string
{
    case IS = 'IS';
    case IS_NOT = 'IS NOT';
    case MATCHS = '=';
    case DOES_NOT_MATCH = '!=';
    case LESS_THAN = '<';
    case MORE_THAN = '>';
    case EQ_OR_LESSTHAN = '<=';
    case EQ_OR_MORETHAN = '>=';
    case ENDS_WITH = "% LIKE";
    case STARTS_WITH = "LIKE %";
    case CONTAINS = "% LIKE %";
    case IN_LIST = "IN";
    case NOT_IN = "NOT IN";
}
