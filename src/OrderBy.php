<?php

namespace MrMadClown\Mnemosyne;

/**
 * @internal
 */
final class OrderBy implements \Stringable
{
    public function __construct(
        public string    $column,
        public Direction $direction = Direction::DESC
    )
    {
    }

    public function __toString(): string
    {
        return "$this->column {$this->direction->value}";
    }
}
