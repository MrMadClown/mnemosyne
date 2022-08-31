<?php

namespace MrMadClown\Mnemosyne;

/**
 * @internal
 */
final class Join implements \Stringable
{
    public function __construct(
        public readonly string    $table,
        public readonly string    $left,
        public readonly string    $right,
        public readonly Operator  $operator = Operator::EQUALS,
        public readonly ?JoinType $type = null,
        public readonly array     $bindings = []
    )
    {
    }

    public function __toString(): string
    {
        return isset($this->type)
            ? "{$this->type->value} JOIN $this->table ON $this->left {$this->operator->value} $this->right"
            : "JOIN $this->table ON $this->left {$this->operator->value} $this->right";
    }
}
