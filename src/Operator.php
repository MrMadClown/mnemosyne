<?php

namespace MrMadClown\Mnemosyne;

enum Operator: string
{
    case EQUALS = "=";
    case NOT_EQUALS = "!=";
    case GREATER = '>';
    case GREATER_EQUALS = '>=';
    case LESS = '<';
    case LESS_EQUALS = '<=';

    case IN = 'IN';
    case NOT_IN = 'NOT IN';

    case IS = 'IS';
    case IS_NOT = 'IS NOT';

    public function expectsArray(): bool
    {
        return $this === Operator::IN
            || $this === Operator::NOT_IN;
    }
}
