<?php

namespace MrMadClown\Mnemosyne;

enum JoinType: string
{
    case INNER = 'INNER';

    case LEFT = 'LEFT';
    case LEFT_OUTER = 'LEFT OUTER';

    case RIGHT = 'RIGHT';
    case RIGHT_OUTER = 'RIGHT OUTER';

    case CROSS = 'CROSS';
}
