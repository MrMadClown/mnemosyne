<?php

namespace MrMadClown\Mnemosyne;

/**
 * @internal
 */
final class Join implements \Stringable
{
    /** @param array<int, Binding> $bindings */
    public function __construct(
        public readonly string    $table,
        public readonly string    $left,
        public readonly string    $right,
        public readonly Operator  $operator = Operator::EQUALS,
        public readonly ?JoinType $type = null,
        public readonly ?string   $alias = null,
        public readonly array     $bindings = [],
    )
    {
    }

    public function __toString(): string
    {
        $tableWithAlias = isset($this->alias) ? "$this->table AS $this->alias" : $this->table;
        $join = isset($this->type) ? "{$this->type->value} JOIN" : "JOIN";

        return "$join $tableWithAlias ON $this->left {$this->operator->value} $this->right";
    }
}
