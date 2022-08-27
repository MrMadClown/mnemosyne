<?php

namespace MrMadClown\Mnemosyne;

interface Repository
{
    public function retrieve(string|int $primary): Model;

    public function destroy(string|int $primary): bool;

    public function persist(Model $model): Model;
}
