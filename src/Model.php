<?php

namespace MrMadClown\Mnemosyne;

interface Model
{
    public static function getTableName(): string;

    public function exists(): bool;
}
