<?php

namespace MrMadClown\Mnemosyne;

abstract class BaseRepository implements Repository
{
    /** @var class-string */
    private readonly string $modelClassName;
    private readonly string $table;

    public function __construct(
        protected readonly \PDO $connection,
        Model                   $model
    )
    {
        $this->modelClassName = $model::class;
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
