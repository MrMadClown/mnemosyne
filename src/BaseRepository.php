<?php

namespace MrMadClown\Mnemosyne;

abstract class BaseRepository implements Repository
{
    private readonly string $modelClassName;
    private readonly string $table;

    public function __construct(
        protected readonly \PDO $connection,
        Model                   $model)
    {
        $this->modelClassName = get_class($model);
        $this->table = $model::getTableName();
    }

    public function destroy(int|string $primary): bool
    {
        return $this->newQueryBuilder()
            ->where('id', $primary)
            ->delete();
    }

    protected function newQueryBuilder(): Builder
    {
        return ((new Builder($this->connection))
            ->table($this->table))
            ->setClassName($this->modelClassName);
    }
}
