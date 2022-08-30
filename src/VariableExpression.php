<?php

namespace MrMadClown\Mnemosyne;

class VariableExpression extends Expression
{
    public function __construct(string $expression, private readonly array $bindings)
    {
        parent::__construct($expression);
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public static function __callStatic(string $method, array $args): Expression
    {
        $param = implode(', ', array_fill(0, count($args), '?'));

        return new VariableExpression(sprintf('%s(%s)', $method, $param), $args);
    }
}
