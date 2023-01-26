<?php

namespace MrMadClown\Mnemosyne;

class VariableExpression extends Expression
{
    /** @param array<int, mixed> $bindings */
    public function __construct(string $expression, private readonly array $bindings)
    {
        parent::__construct($expression);
    }

    /** @return array<int, mixed> */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /** @param array<int, mixed> $args */
    public static function __callStatic(string $method, array $args): Expression
    {
        $bindings = [];
        $param = implode(', ', array_map(function (mixed $val) use (&$bindings): string {
            if ($val instanceof Expression) {
                if ($val instanceof VariableExpression) {
                    foreach ($val->getBindings() as $binding) {
                        $bindings[] = $binding;
                    }
                }
                return $val;
            }
            $bindings[] = $val;
            return '?';
        }, $args));

        return new VariableExpression(sprintf('%s(%s)', $method, $param), $bindings);
    }
}
