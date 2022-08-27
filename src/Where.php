<?php

namespace MrMadClown\Mnemosyne;

final class Where
{
    public function __construct(
        public readonly string   $column,
        public readonly mixed    $value = null,
        public readonly Operator $operator = Operator::EQUALS,
        public readonly Logical  $boolean = Logical::AND
    )
    {
    }
}
