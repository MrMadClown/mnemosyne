<?php

namespace MrMadClown\Mnemosyne;

/**
 * @internal
 */
final class Binding
{
    public function __construct(
        public readonly mixed $value,
        public readonly int   $type
    )
    {

    }
}
