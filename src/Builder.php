<?php

namespace MrMadClown\Mnemosyne;

use PDO;
use PDOStatement;

class Builder
{
    protected int $fetchMode = \PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE;
    protected string $className;

    protected string $table;
    protected array $columns = ['*'];
    /** @var array<Join> */
    protected array $joins = [];
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $groupBy = [];
    protected int $limit;

    protected string $orderBy;
    protected Direction $orderByDirection = Direction::DESC;

    public function __construct(protected readonly \PDO $connection)
    {
    }

    //<editor-fold description="public Builder interface">

    public function from(string $table): Builder
    {
        return $this->table($table);
    }

    public function into(string $table): Builder
    {
        return $this->table($table);
    }

    public function table(string $table): Builder
    {
        $this->table = $table;
        return $this;
    }

    public function select(array|string $columns): Builder
    {
        $this->columns = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    public function count(string $column = '*'): Builder
    {
        $this->columns = ["COUNT($column)"];
        return $this;
    }

    public function where(
        callable|string $column,
        mixed           $value = null,
        Operator        $operator = Operator::EQUALS,
        Logical         $boolean = Logical::AND
    ): Builder
    {
        if (is_callable($column)) { //TODO function names
            if (!empty($this->wheres)) {
                $wheres = $this->wheres;
                $this->wheres = [];
            }
            call_user_func($column, $this);
            if (isset($wheres)) {
                $this->wheres = [$wheres, $this->wheres];
            } else {
                $this->wheres = [$this->wheres];
            }
        } else {
            $this->wheres[] = new Where($column, $value, $operator, $boolean);
        }

        return $this;
    }

    public function orWhere(callable|string $column, mixed $value, Operator $operator = Operator::EQUALS): Builder
    {
        return $this->where($column, $value, $operator, Logical::OR);
    }

    public function orWhereNot(callable|string $column, mixed $value): Builder
    {
        return $this->orWhere($column, $value, Operator::NOT_EQUALS);
    }

    public function whereIsNull(string $column): Builder
    {
        return $this->where($column, null, Operator::IS);
    }

    public function whereIsNotNull(string $column): Builder
    {
        return $this->where($column, null, Operator::IS_NOT);
    }

    public function orWhereIsNull(string $column): Builder
    {
        return $this->orWhere($column, null, Operator::IS);
    }

    public function orWhereIsNotNull(string $column): Builder
    {
        return $this->orWhere($column, null, Operator::IS_NOT);
    }

    public function whereIn(string $column, array $value): Builder
    {
        return $this->where($column, $value, Operator::IN);
    }

    public function orWhereIn(string $column, array $value): Builder
    {
        return $this->orWhere($column, $value, Operator::IN);
    }

    public function whereNotIn(string $column, array $value): Builder
    {
        return $this->where($column, $value, Operator::NOT_IN);
    }

    public function orWhereNotIn(string $column, array $value): Builder
    {
        return $this->orWhere($column, $value, Operator::NOT_IN);
    }

    public function whereLike(string $column, string $value): Builder
    {
        return $this->where($column, $value, Operator::LIKE);
    }

    public function orWhereLike(string $column, string $value): Builder
    {
        return $this->orWhere($column, $value, Operator::LIKE);
    }

    /**
     * @todo add callable as $left param type => call with new Builder instance => ad `as` method, should only compile select statements
     */
    public function join(string $table, string $left, string $right, Operator $operator = Operator::EQUALS, JoinType $type = null): Builder
    {
        $this->joins[] = new Join($table, $left, $right, $operator, $type);
        return $this;
    }

    public function leftJoin(string $table, string $left, string $right, Operator $operator = Operator::EQUALS): Builder
    {
        return $this->join($table, $left, $right, $operator, JoinType::LEFT);
    }

    public function leftOuterJoin(string $table, string $left, string $right, Operator $operator = Operator::EQUALS): Builder
    {
        return $this->join($table, $left, $right, $operator, JoinType::LEFT_OUTER);
    }

    public function rightJoin(string $table, string $left, string $right, Operator $operator = Operator::EQUALS): Builder
    {
        return $this->join($table, $left, $right, $operator, JoinType::RIGHT);
    }

    public function rightOuterJoin(string $table, string $left, string $right, Operator $operator = Operator::EQUALS): Builder
    {
        return $this->join($table, $left, $right, $operator, JoinType::RIGHT_OUTER);
    }

    public function crossJoin(string $table, string $left, string $right, Operator $operator = Operator::EQUALS): Builder
    {
        return $this->join($table, $left, $right, $operator, JoinType::CROSS);
    }

    public function limit(int $limit): Builder
    {
        $this->limit = $limit;
        return $this;
    }

    public function groupBy(string|array $groupBy): Builder
    {
        $this->groupBy = is_array($groupBy) ? $groupBy : [$groupBy];
        return $this;
    }

    public function orderBy(string $orderBy, Direction $direction = Direction::DESC): Builder
    {
        $this->orderBy = $orderBy;
        $this->orderByDirection = $direction;
        return $this;
    }

    public function orderByAsc(string $orderBy): Builder
    {
        return $this->orderBy($orderBy, Direction::ASC);
    }

    //</editor-fold>

    //<editor-fold description="Internal query Builder">

    protected function compileSelect(): string
    {
        $columns = implode(', ', $this->columns);
        return $this->processLimit(
                $this->processOrderBy(
                    $this->processGroupBy(
                        $this->processWheres(
                            $this->processJoins(
                                "SELECT $columns FROM $this->table"
                            )
                        )
                    )
                )
            ) . ';';
    }

    protected function compileUpdate(array $values): string
    {
        $setString = implode(', ', array_map(function ($column, $value) {
            $param = $this->compileBinding($value);

            return "$column = $param";
        }, array_keys($values), $values));

        return $this->processLimit($this->processOrderBy($this->processWheres("UPDATE $this->table SET $setString"))) . ';';
    }

    protected function compileInsert(array $values, bool $ignore = false): string
    {
        $valueParam = $this->compileArrayBinding($values);
        $columns = implode(', ', array_keys($values));
        $insert = $ignore ? 'INSERT IGNORE' : 'INSERT';
        return "$insert INTO $this->table ($columns) VALUES ($valueParam);";
    }

    protected function compileDelete(): string
    {
        return $this->processLimit($this->processOrderBy($this->processWheres("DELETE FROM $this->table"))) . ';';
    }

    protected function compileWheres(array $wheres): string
    {
        $wheres = array_filter($wheres, static fn($w) => !empty($w));
        return implode(' ', array_map(function (array|Where $where, int $idx): string {
                if ($where instanceof Where) {
                    return $this->compileWhere($where, $idx !== 0);
                }
                if (count($where) === 1) {
                    $where = $where[0];
                    return $this->compileWhere($where, $idx !== 0);
                }
                return $idx === 0
                    ? '(' . $this->compileWheres($where) . ')'
                    : $where[0]->boolean->value . ' ' . '(' . $this->compileWheres($where) . ')';
            }, $wheres, array_keys($wheres))
        );
    }

    protected function compileWhere(Where $where, bool $withBoolean = false): string
    {
        if ($where->operator->expectsArray() && is_array($where->value)) {
            $param = '(' . $this->compileArrayBinding($where->value) . ')';
        } else {
            $param = $this->compileBinding($where->value);
        }

        return $withBoolean
            ? "{$where->boolean->value} $where->column {$where->operator->value} $param"
            : "$where->column {$where->operator->value} $param";
    }

    protected function compileArrayBinding(array $value): string
    {
        return implode(', ', array_map(fn(mixed $val): string => $this->compileBinding($val), $value));
    }

    protected function compileBinding(mixed $value): string
    {
        if ($value instanceof Expression) {
            if ($value instanceof VariableExpression) {
                $this->addBindings($value->getBindings());
            }
            return (string)$value;
        }
        $this->addBinding($value);
        return '?';
    }

    protected function addBindings(array $bindings): void
    {
        foreach ($bindings as $binding) {
            $this->addBinding($binding);
        }
    }

    protected function addBinding(mixed $binding): void
    {
        $this->bindings[] = match (true) {
            is_null($binding) => [null, \PDO::PARAM_NULL],
            is_bool($binding) => [$binding, \PDO::PARAM_BOOL],
            is_int($binding) => [$binding, \PDO::PARAM_INT],
            is_float($binding), is_string($binding) => [$binding, \PDO::PARAM_STR],
            is_array($binding), is_object($binding) => [json_encode($binding), \PDO::PARAM_STR],
            default => throw new \InvalidArgumentException(sprintf('Bindings with type %s are not allowed', gettype($binding)))
        };
    }

    protected function bindValues(PDOStatement $statement): void
    {
        foreach ($this->bindings as $idx => $binding) {
            $statement->bindValue($idx + 1, $binding[0], $binding[1]);
        }
    }

    protected function processWheres(string $statement): string
    {
        if (!empty($this->wheres)) {
            return implode(' ', [$statement, 'WHERE', $this->compileWheres($this->wheres)]);
        }
        return $statement;
    }

    protected function processOrderBy(string $statement): string
    {
        if (isset($this->orderBy)) {
            return implode(' ', [$statement, 'ORDER BY', $this->orderBy, $this->orderByDirection->value]);
        }
        return $statement;
    }

    protected function processLimit(string $statement): string
    {
        if (isset($this->limit)) {
            return implode(' ', [$statement, 'LIMIT', $this->limit]);
        }
        return $statement;
    }

    protected function processGroupBy(string $statement): string
    {
        if (!empty($this->groupBy)) {
            return implode(' ', [$statement, 'GROUP BY', implode(', ', $this->groupBy)]);
        }
        return $statement;
    }

    protected function processJoins(string $statement): string
    {
        if (!empty($this->joins)) {
            return implode(' ', [$statement, implode(' ', $this->joins)]);
        }
        return $statement;
    }

    //</editor-fold>

    //<editor-fold description="Internal PDO">
    protected function prepareSelect(): PDOStatement
    {
        return $this->prepareStatement($this->compileSelect());
    }

    protected function prepareFetch(array $constructorArgs = []): PDOStatement
    {
        $statement = $this->prepareSelect();
        $this->bindValues($statement);
        $statement->setFetchMode($this->fetchMode, $this->className, ...$constructorArgs);
        $statement->execute();
        return $statement;
    }

    protected function prepareUpdate(array $values): PDOStatement
    {
        return $this->prepareStatement($this->compileUpdate($values));
    }

    protected function prepareInsert(array $values, bool $ignore = false): PDOStatement
    {
        return $this->prepareStatement($this->compileInsert($values, $ignore));
    }

    protected function prepareDelete(): PDOStatement
    {
        return $this->prepareStatement($this->compileDelete());
    }

    protected function prepareStatement(string $statement): PDOStatement|false
    {
        return $this->connection->prepare($statement, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    //</editor-fold>

    //<editor-fold description="Public execute Query">

    public function fetch(...$constructorArgs): mixed
    {
        return $this->prepareFetch($constructorArgs)->fetch();
    }

    public function fetchAll(...$constructorArgs): array
    {
        return $this->prepareFetch($constructorArgs)->fetchAll();
    }

    public function update(array $values): void
    {
        $statement = $this->prepareUpdate($values);
        $this->bindValues($statement);
        $statement->execute();
    }

    public function insert(array $values): int
    {
        $statement = $this->prepareInsert($values);
        $this->bindValues($statement);
        $statement->execute();
        return (int)$this->connection->lastInsertId();
    }

    public function insertIgnore(array $values): int
    {
        $statement = $this->prepareInsert($values, true);
        $this->bindValues($statement);
        $statement->execute();
        return (int)$this->connection->lastInsertId();
    }

    public function delete(): bool
    {
        $statement = $this->prepareDelete();
        $this->bindValues($statement);
        return $statement->execute();
    }

    //</editor-fold>

    public function setFetchMode(int $fetchMode): Builder
    {
        $this->fetchMode = $fetchMode;
        return $this;
    }

    public function setClassName(string $className): Builder
    {
        $this->className = $className;
        return $this;
    }
}
