<?php

namespace MrMadClown\Mnemosyne;

use ArgumentCountError;
use BadMethodCallException;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use function array_filter;
use function array_keys;
use function array_map;
use function call_user_func;
use function count;
use function debug_backtrace;
use function gettype;
use function implode;
use function is_array;
use function is_bool;
use function is_callable;
use function is_float;
use function is_int;
use function is_null;
use function is_object;
use function is_string;
use function json_encode;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function substr;

/**
 * @method Builder whereNot(string $column, mixed $value = null)
 * @method Builder whereIs(string $column, mixed $value = null)
 * @method Builder whereIsNull(string $column)
 * @method Builder whereIsNot(string $column, mixed $value = null)
 * @method Builder whereIn(string $column, array $value)
 * @method Builder whereNotIn(string $column, array $value)
 * @method Builder whereLike(string $column, string $value)
 * @method Builder whereLess(string $column, mixed $value = null)
 * @method Builder whereGreater(string $column, mixed $value = null)
 *
 * @method Builder orWhere(string $column, mixed $value = null, Operator $operator = Operator::EQUALS)
 * @method Builder orWhereNot(string $column, mixed $value = null)
 * @method Builder orWhereIs(string $column, mixed $value = null)
 * @method Builder orWhereIsNull(string $column)
 * @method Builder orWhereIsNot(string $column, mixed $value = null)
 * @method Builder orWhereIn(string $column, array $value)
 * @method Builder orWhereNotIn(string $column, array $value)
 * @method Builder orWhereLike(string $column, string $value)
 * @method Builder orWhereLess(string $column, mixed $value = null)
 * @method Builder orWhereGreater(string $column, mixed $value = null)
 *
 * @method Builder xorWhere(string $column, mixed $value = null, Operator $operator = Operator::EQUALS)
 * @method Builder xorWhereNot(string $column, mixed $value = null)
 * @method Builder xorWhereIs(string $column, mixed $value = null)
 * @method Builder xorWhereIsNull(string $column)
 * @method Builder xorWhereIsNot(string $column, mixed $value = null)
 * @method Builder xorWhereIn(string $column, array $value)
 * @method Builder xorWhereLike(string $column, string $value)
 * @method Builder xorWhereLess(string $column, mixed $value = null)
 * @method Builder xorWhereGreater(string $column, mixed $value = null)
 *
 * @method Builder crossJoin(callable|string $table, string $left, string $right, Operator $operator = Operator::EQUALS, string $alias = null)
 * @method Builder leftJoin(callable|string $table, string $left, string $right, Operator $operator = Operator::EQUALS, string $alias = null)
 * @method Builder leftOuterJoin(callable|string $table, string $left, string $right, Operator $operator = Operator::EQUALS, string $alias = null)
 * @method Builder rightJoin(callable|string $table, string $left, string $right, Operator $operator = Operator::EQUALS, string $alias = null)
 * @method Builder rightOuterJoin(callable|string $table, string $left, string $right, Operator $operator = Operator::EQUALS, string $alias = null)
 */
class Builder
{
    protected int $fetchMode = PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE;

    /** @var class-string */
    protected string $className;

    protected string $table;
    protected string $alias;

    /** @var array<int, string> */
    protected array $columns = ['*'];

    /** @var array<int, Join> */
    protected array $joins = [];

    /** @var array<int, Where|array<int, Where>> */
    protected array $wheres = [];

    /** @var array<int, Where|array<int, Where>> */
    protected array $havings = [];

    /** @var array<int, Binding> */
    protected array $bindings = [];

    /** @var array<int, string> */
    protected array $groupBy = [];

    /** @var array<int, OrderBy> */
    protected array $orderBy = [];

    protected int $limit;
    protected int $offset;

    public function __construct(protected readonly PDO $connection)
    {
    }

    //<editor-fold description="public Builder interface">

    public function as(string $alias): Builder
    {
        $this->alias = $alias;
        return $this;
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

    /** @param string|array<string> $columns */
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

    public function having(
        callable|string $column,
        mixed           $value = null,
        Operator        $operator = Operator::EQUALS,
        Logical         $boolean = Logical::AND
    ): Builder
    {
        if (is_callable($column)) { //TODO function names
            if (!empty($this->havings)) {
                $havings = $this->havings;
                $this->havings = [];
            }
            call_user_func($column, $this);
            if (isset($havings)) {
                $this->havings = [$havings, $this->havings];
            } else {
                $this->havings = [$this->havings];
            }
        } else if (is_callable($value)) { //TODO function names
            $builder = new Builder($this->connection);
            call_user_func($value, $builder);
            if (isset($builder->table)) {
                $this->havings[] = new Where($column, new VariableExpression($builder->compileSubSelect(), $builder->bindings), $operator, $boolean);
            } else {
                $this->havings = empty($this->havings) ? [$builder->havings] : [$this->havings, $builder->havings];
            }
        } else {
            $this->havings[] = new Where($column, $value, $operator, $boolean);
        }

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
        } else if (is_callable($value)) { //TODO function names
            $builder = new Builder($this->connection);
            call_user_func($value, $builder);
            if (isset($builder->table)) {
                $this->wheres[] = new Where($column, new VariableExpression($builder->compileSubSelect(), $builder->bindings), $operator, $boolean);
            } else {
                $this->wheres = empty($this->wheres) ? [$builder->wheres] : [$this->wheres, $builder->wheres];
            }
        } else {
            $this->wheres[] = new Where($column, $value, $operator, $boolean);
        }

        return $this;
    }

    public function join(callable|string $table, string $left, string $right, Operator $operator = Operator::EQUALS, JoinType $type = null, string $alias = null): Builder
    {
        if (is_callable($table)) { //TODO function names
            $builder = new Builder($this->connection);
            call_user_func($table, $builder);
            $this->joins[] = new Join($builder->compileSubSelect(), $left, $right, $operator, $type, $alias, $builder->bindings);
        } else {
            $this->joins[] = new Join($table, $left, $right, $operator, $type, $alias);
        }
        return $this;
    }

    public function limit(int $limit): Builder
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): Builder
    {
        $this->offset = $offset;
        return $this;
    }

    /** @param string|array<string> $groupBy */
    public function groupBy(string|array $groupBy): Builder
    {
        $this->groupBy = is_array($groupBy) ? $groupBy : [$groupBy];
        return $this;
    }

    public function orderBy(string $orderBy, Direction $direction = Direction::DESC): Builder
    {
        $this->orderBy[] = new OrderBy($orderBy, $direction);
        return $this;
    }

    public function orderByAsc(string $orderBy): Builder
    {
        return $this->orderBy($orderBy, Direction::ASC);
    }

    /** @param array{0: string, 1?: mixed, 2?: Operator}|array{0: string, 1: string, 2: string, 3?: Operator, 4?: string} $arguments */
    public function __call(string $name, array $arguments): Builder
    {
        return match (true) {
            str_contains(strtolower($name), 'having') => $this->callMagicWhere('having', $name, $arguments),
            str_contains(strtolower($name), 'where') => $this->callMagicWhere('where', $name, $arguments),
            str_ends_with($name, 'Join') => $this->callMagicJoin($name, $arguments),
            default => self::throwBadMethodCallException($name, debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0])
        };
    }

    /** @param array{0?: string, 1?: mixed, 2?: Operator} $arguments */
    protected function callMagicWhere(string $method, string $name, array $arguments): Builder
    {
        $column = $arguments[0] ?? self::throwArgumentCountError(
            $name,
            0,
            debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1],
            1,
        );

        $fullName = $name;
        if (str_ends_with($name, 'Null')) {
            $name = substr($name, 0, -4);
            $value = null;
        } else {
            $value = $arguments[1] ?? null;
        }

        $boolean = match (true) {
            str_starts_with($name, 'or') => Logical::OR,
            str_starts_with($name, 'xor') => Logical::XOR,
            default => Logical::AND
        };

        $operator = match (true) {
            str_ends_with($name, 'IsNot') => Operator::IS_NOT,
            str_ends_with($name, 'Not') => Operator::NOT_EQUALS,
            str_ends_with($name, 'Is') => Operator::IS,
            str_ends_with($name, 'NotIn') => Operator::NOT_IN,
            str_ends_with($name, 'In') => Operator::IN,
            str_ends_with($name, 'Like') => Operator::LIKE,
            str_ends_with($name, 'Less') => Operator::LESS,
            str_ends_with($name, 'Greater') => Operator::GREATER,
            str_ends_with($name, ucfirst($method)) => $arguments[2] ?? Operator::EQUALS,
            default => self::throwBadMethodCallException($fullName, debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1])
        };
        return $this->$method($column, $value, $operator, $boolean);
    }

    /** @param array{0?: string, 1?: string, 2?: string, 3?: Operator, 4?: string} $arguments */
    protected function callMagicJoin(string $name, array $arguments): Builder
    {
        if (count($arguments) < 3) self::throwArgumentCountError(
            $name,
            count($arguments),
            debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1],
            3
        );
        [$table, $left, $right] = $arguments;
        $type = match (true) {
            str_starts_with($name, 'cross') => JoinType::CROSS,
            str_starts_with($name, 'leftOuter') => JoinType::LEFT_OUTER,
            str_starts_with($name, 'left') => JoinType::LEFT,
            str_starts_with($name, 'rightOuter') => JoinType::RIGHT_OUTER,
            str_starts_with($name, 'right') => JoinType::RIGHT,
            default => self::throwBadMethodCallException($name, debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1])
        };
        return $this->join($table, $left, $right, $arguments[3] ?? Operator::EQUALS, $type, $arguments[4] ?? null);
    }
    //</editor-fold>

    //<editor-fold description="Internal query Builder">

    protected function compileSelect(): string
    {
        $columns = implode(', ', $this->columns);
        return $this->processOffset(
            $this->processLimit(
                $this->processOrderBy(
                    $this->processHaving(
                        $this->processGroupBy(
                            $this->processWheres(
                                $this->processJoins(
                                    "SELECT $columns FROM $this->table"
                                )
                            )
                        )
                    )
                )
            )
        );
    }

    protected function compileSubSelect(): string
    {
        return $this->processAlias($this->compileSelect());
    }

    /** @param array<string, mixed> $values */
    protected function compileUpdate(array $values): string
    {
        $setString = implode(', ', array_map(function (string $column, mixed $value): string {
            $param = $this->compileBinding($value);

            return "$column = $param";
        }, array_keys($values), $values));

        return $this->processOffset(
                $this->processLimit(
                    $this->processOrderBy(
                        $this->processWheres("UPDATE $this->table SET $setString")
                    )
                )
            ) . ';';
    }

    /** @param array<string, mixed> $values */
    protected function compileInsert(array $values, bool $ignore): string
    {
        $valueParam = $this->compileArrayBinding($values);
        $columns = implode(', ', array_keys($values));
        $insert = $ignore ? 'INSERT IGNORE' : 'INSERT';
        return "$insert INTO $this->table ($columns) VALUES ($valueParam);";
    }

    protected function compileDelete(): string
    {
        return $this->processOffset(
                $this->processLimit(
                    $this->processOrderBy(
                        $this->processWheres(
                            "DELETE FROM $this->table"
                        )
                    )
                )
            ) . ';';
    }

    /** @param array<int, Where|array<int, Where>> $wheres */
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

    /** @param array<int, Join> $joins */
    protected function compileJoins(array $joins): string
    {
        return implode(' ', array_map(function (Join $join): string {
                if (!empty($join->bindings)) {
                    foreach ($join->bindings as $binding) {
                        $this->addBinding($binding);
                    }
                }

                return $join;
            }, $joins)
        );
    }

    protected function compileWhere(Where $where, bool $withBoolean): string
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

    /** @param array<int|string, mixed> $value */
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
            return $value;
        }
        $this->addBinding($value);
        return '?';
    }

    /** @param array<int, mixed> $bindings */
    protected function addBindings(array $bindings): void
    {
        foreach ($bindings as $binding) {
            $this->addBinding($binding);
        }
    }

    protected function addBinding(mixed $binding): void
    {
        $this->bindings[] = match (true) {
            is_null($binding) => new Binding(null, PDO::PARAM_NULL),
            is_bool($binding) => new Binding($binding, PDO::PARAM_BOOL),
            is_int($binding) => new Binding($binding, PDO::PARAM_INT),
            is_float($binding), is_string($binding) => new Binding($binding, PDO::PARAM_STR),
            $binding instanceof Binding => $binding,
            is_array($binding), is_object($binding) => new Binding(json_encode($binding), PDO::PARAM_STR),
            default => throw new InvalidArgumentException(sprintf('Bindings with type %s are not allowed', gettype($binding)))
        };
    }

    protected function bindValues(PDOStatement $statement): void
    {
        foreach ($this->bindings as $idx => $binding) {
            $statement->bindValue($idx + 1, $binding->value, $binding->type);
        }
    }

    protected function processWheres(string $statement): string
    {
        if (!empty($this->wheres)) {
            return sprintf('%s WHERE %s', $statement, $this->compileWheres($this->wheres));
        }
        return $statement;
    }

    protected function processOrderBy(string $statement): string
    {
        if (!empty($this->orderBy)) {
            return sprintf('%s ORDER BY %s', $statement, implode(', ', $this->orderBy));
        }
        return $statement;
    }

    protected function processLimit(string $statement): string
    {
        if (isset($this->limit)) {
            return sprintf('%s LIMIT %s', $statement, $this->limit);
        }
        return $statement;
    }

    protected function processOffset(string $statement): string
    {
        if (isset($this->offset)) {
            return sprintf('%s OFFSET %s', $statement, $this->offset);
        }
        return $statement;
    }

    protected function processGroupBy(string $statement): string
    {
        if (!empty($this->groupBy)) {
            return sprintf('%s GROUP BY %s', $statement, implode(', ', $this->groupBy));
        }
        return $statement;
    }

    protected function processHaving(string $statement): string
    {
        if (!empty($this->havings)) {
            return sprintf('%s HAVING %s', $statement, $this->compileWheres($this->havings));
        }
        return $statement;
    }

    protected function processJoins(string $statement): string
    {
        if (!empty($this->joins)) {
            return implode(' ', [$statement, $this->compileJoins($this->joins)]);
        }
        return $statement;
    }

    protected function processAlias(string $statement): string
    {
        if (isset($this->alias)) {
            return sprintf('(%s) AS %s', $statement, $this->alias);
        }
        return sprintf('(%s)', $statement);
    }

    //</editor-fold>

    //<editor-fold description="Internal PDO">
    protected function prepareSelect(): PDOStatement
    {
        return $this->prepareStatement($this->compileSelect() . ';');
    }

    /** @param array<int, mixed> $constructorArgs */
    protected function prepareFetch(array $constructorArgs = []): PDOStatement
    {
        $statement = $this->prepareSelect();
        $this->bindValues($statement);
        $statement->setFetchMode($this->fetchMode, $this->className ?? null, ...$constructorArgs);
        $statement->execute();
        return $statement;
    }

    /** @param array<string, mixed> $values */
    protected function prepareUpdate(array $values): PDOStatement
    {
        return $this->prepareStatement($this->compileUpdate($values));
    }

    /** @param array<string, mixed> $values */
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

    public function fetch(mixed ...$constructorArgs): mixed
    {
        return $this->prepareFetch($constructorArgs)->fetch();
    }

    /** @return array<int, mixed> */
    public function fetchAll(mixed ...$constructorArgs): array
    {
        return $this->prepareFetch($constructorArgs)->fetchAll();
    }

    /** @param array<string, mixed> $values */
    public function update(array $values): void
    {
        $statement = $this->prepareUpdate($values);
        $this->bindValues($statement);
        $statement->execute();
    }

    /** @param array<string, mixed> $values */
    public function insert(array $values): int
    {
        $statement = $this->prepareInsert($values);
        $this->bindValues($statement);
        $statement->execute();
        return (int)$this->connection->lastInsertId();
    }

    /** @param array<string, mixed> $values */
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

    /** @param class-string $className */
    public function setClassName(string $className): Builder
    {
        $this->className = $className;
        return $this;
    }

    /** @param array{function: string, line: int, file: string, class?: class-string, type?: string, args?: array<int|string, mixed>, object?: object} $backTrace */
    private static function throwArgumentCountError(string $methodName, int $count, array $backTrace, int $expectedCount): never
    {
        throw new ArgumentCountError(
            sprintf(
                'Too few arguments to function %s::%s(), %s passed in %s on line %s and exactly %s expected',
                static::class,
                $methodName,
                $count,
                $backTrace['file'],
                $backTrace['line'],
                $expectedCount
            )
        );
    }

    /** @param array{function: string, line: int, file: string, class?: class-string, type?: string, args?: array<int|string, mixed>, object?: object} $backTrace */
    private static function throwBadMethodCallException(string $methodName, array $backTrace): never
    {
        throw new BadMethodCallException(
            sprintf(
                'Call to undefined method %s::%s() in %s:%s',
                static::class,
                $methodName,
                $backTrace['file'],
                $backTrace['line'],
            )
        );
    }
}
