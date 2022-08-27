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

    public function where(
        callable|string $column,
        mixed           $value = null,
        Operator        $operator = Operator::EQUALS,
        Logical         $boolean = Logical::AND
    ): Builder
    {
        if (is_callable($column)) {
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
        return $this->where($column, $value, Operator::NOT_EQUALS, Logical::OR);
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

    protected function compileSelect(): PDOStatement
    {
        $columns = implode(', ', $this->columns);
        $statement = $this->processLimit(
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

        return $this->prepareStatement($statement);
    }

    protected function compileUpdate(array $values): PDOStatement
    {
        $setString = implode(', ', array_map(function ($column, $value) {
            if ($value instanceof Expression) {
                return "$column = {$value->expression}";
            }
            $this->addBinding($value);
            return "$column = ?";
        }, array_keys($values), $values));

        $statement = $this->processLimit($this->processOrderBy($this->processWheres("UPDATE $this->table SET $setString"))) . ';';
        return $this->prepareStatement($statement);
    }

    protected function compileInsert(array $values, bool $ignore = false): PDOStatement
    {
        $valueParam = implode(', ', array_map(function ($column, $value) {
            if ($value instanceof Expression) {
                return $value->expression;
            }
            $this->addBinding($value);
            return '?';
        }, array_keys($values), $values));

        $columns = implode(', ', array_keys($values));
        $insert = $ignore ? 'INSERT IGNORE' : 'INSERT';
        $statement = "$insert INTO $this->table ($columns) VALUES ($valueParam);";
        return $this->connection->prepare($statement);
    }

    protected function compileDelete(): PDOStatement
    {
        $statement = $this->processLimit($this->processOrderBy($this->processWheres("DELETE FROM $this->table"))) . ';';

        return $this->prepareStatement($statement);
    }

    protected function compileWheres(array $wheres): string
    {
        $wheres = array_filter($wheres, static fn($w) => !empty($w));
        return implode(" ", array_map(function (array|Where $where, int $idx): string {
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
        $compiled = $withBoolean
            ? "{$where->boolean->value} $where->column {$where->operator->value}"
            : "$where->column {$where->operator->value}";
        if ($where->operator->expectsArray() && is_array($where->value)) {
            foreach ($where->value as $val) {
                $this->addBinding($val);
            }
            $param = implode(', ', array_fill(0, count($where->value), '?'));
            return "$compiled ($param)";
        } else {
            if ($where->value instanceof Expression) {
                return "$compiled {$where->value->expression}";
            } else {
                $this->addBinding($where->value);
                return "$compiled ?";
            }
        }
    }

    protected function addBinding(mixed $value): void
    {
        $this->bindings[] = match (true) {
            is_null($value) => [null, \PDO::PARAM_NULL],
            is_bool($value) => [$value, \PDO::PARAM_BOOL],
            is_int($value) => [$value, \PDO::PARAM_INT],
            is_float($value), is_string($value) => [$value, \PDO::PARAM_STR],
            is_array($value), is_object($value) => [json_encode($value), \PDO::PARAM_STR],
            default => throw new \InvalidArgumentException(sprintf('Bindings with type %s are not allowed', gettype($value)))
        };
    }

    protected function prepareFetch(array $constructorArgs = []): PDOStatement
    {
        $statement = $this->compileSelect();
        $this->bindValues($statement);
        $statement->setFetchMode($this->fetchMode, $this->className, ...$constructorArgs);
        $statement->execute();
        return $statement;
    }

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
        $statement = $this->compileUpdate($values);
        $this->bindValues($statement);
        $statement->execute();
    }

    public function insert(array $values): int
    {
        $statement = $this->compileInsert($values);
        $this->bindValues($statement);
        $statement->execute();
        return (int)$this->connection->lastInsertId();
    }

    public function insertIgnore(array $values): int
    {
        $statement = $this->compileInsert($values, true);
        $this->bindValues($statement);
        $statement->execute();
        return (int)$this->connection->lastInsertId();
    }

    public function delete(): bool
    {
        $statement = $this->compileDelete();
        $this->bindValues($statement);
        return $statement->execute();
    }

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

    protected function bindValues(PDOStatement $statement): void
    {
        foreach ($this->bindings as $idx => $binding) {
            $statement->bindValue($idx + 1, $binding[0], $binding[1]);
        }
    }

    protected function processWheres(string $statement): string
    {
        if (!empty($this->wheres)) {
            $statement .= ' WHERE ' . $this->compileWheres($this->wheres);
        }
        return $statement;
    }

    protected function processOrderBy(string $statement): string
    {
        if (isset($this->orderBy)) {
            $statement .= " ORDER BY {$this->orderBy} {$this->orderByDirection->value}";
        }
        return $statement;
    }

    protected function processLimit(string $statement): string
    {
        if (isset($this->limit)) {
            $statement .= " LIMIT {$this->limit}";
        }
        return $statement;
    }

    protected function processGroupBy(string $statement): string
    {
        if (!empty($this->groupBy)) {
            $statement .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }
        return $statement;
    }

    protected function processJoins(string $statement): string
    {
        if (!empty($this->joins)) {
            $statement .= ' ' . implode(' ', $this->joins);
        }
        return $statement;
    }

    protected function prepareStatement(string $statement): PDOStatement|false
    {
        return $this->connection->prepare($statement, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
}
