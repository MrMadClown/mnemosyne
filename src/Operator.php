<?php

namespace MrMadClown\Mnemosyne;

enum Operator: string
{
    case EQUALS = "=";
    case NULL_SAFE_EQUALS = "<=>";
    case NOT_EQUALS = "!=";
    case GREATER = '>';
    case GREATER_EQUALS = '>=';
    case LESS = '<';
    case LESS_EQUALS = '<=';

    case LIKE = 'LIKE';

    case IN = 'IN';
    case NOT_IN = 'NOT IN';
    case GREATEST = 'GREATEST';
    case COALESCE = 'COALESCE';
    case LEAST = 'LEAST';

    case IS = 'IS';
    case IS_NOT = 'IS NOT';

    public function expectsArray(): bool
    {
        return in_array($this, [Operator::IN, Operator::NOT_IN, Operator::GREATEST, Operator::LEAST, Operator::COALESCE], true);
    }
}
