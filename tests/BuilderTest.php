<?php

namespace MrMadClown\Mnemosyne\Tests;

use MrMadClown\Mnemosyne\Builder;
use MrMadClown\Mnemosyne\Direction;
use MrMadClown\Mnemosyne\Expression;
use MrMadClown\Mnemosyne\Operator;
use MrMadClown\Mnemosyne\VariableExpression;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;

class BuilderTest extends BaseTest
{
    public function testSelectAll()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->fetchAll();
    }

    public function testSelectFetchModeAssoc()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('execute');

        $statement->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_ASSOC, null);

        (new Builder($pdo))
            ->setFetchMode(PDO::FETCH_ASSOC)
            ->from('users')
            ->fetchAll();
    }

    public function testCountAll()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT COUNT(*) FROM users;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('execute');

        $statement->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'User');

        (new Builder($pdo))
            ->setClassName('User')
            ->count()
            ->from('users')
            ->fetchAll();
    }

    public function testSelectAllLimit()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users LIMIT 10;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->limit(10)
            ->fetchAll();
    }

    public function testSelectAllLimitOffset()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users LIMIT 10 OFFSET 5;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->limit(10)
            ->offset(5)
            ->fetchAll();
    }

    public function testSelectSingleColumn()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT id FROM users;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->select('id')
            ->from('users')
            ->fetchAll();
    }

    public function testSelectOderByMultiple()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users ORDER BY id DESC, age ASC;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->orderBy('id')
            ->orderBy('age', Direction::ASC)
            ->fetchAll();
    }

    public function testSelectMultipleColumns()
    {
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT id, name FROM users;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($this->mockStatement());

        (new Builder($pdo))
            ->setClassName('User')
            ->select(['id', 'name'])
            ->from('users')
            ->fetchAll();
    }

    public function testSelectWhere()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('bindValue')
            ->with(1, 25, PDO::PARAM_INT);

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetch');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->fetch();
    }

    public function testSelectWhereExpression()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE updated_at > DATE(now());', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::never())
            ->method('bindValue');

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('updated_at', new Expression('DATE(now())'), Operator::GREATER)
            ->fetchAll();
    }

    public function testSelectWhereNestedExpression()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE updated_at > DATE(NOW());', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::never())
            ->method('bindValue');

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('updated_at', Expression::DATE(Expression::NOW()), Operator::GREATER)
            ->fetchAll();
    }

    public function testSelectWhereVariableExpression()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE hashed_id = crc32(?);', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('bindValue')
            ->with(1, 'my-unique-user-id', PDO::PARAM_STR);

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('hashed_id', VariableExpression::crc32('my-unique-user-id'))
            ->fetchAll();
    }

    public function testSelectWhereNestedVariableExpression()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE hashed_id = crc32(floor(?));', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('bindValue')
            ->with(1, 13.5, PDO::PARAM_STR);

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('hashed_id', VariableExpression::crc32(VariableExpression::floor(13.5)))
            ->fetchAll();
    }

    public function testSelectWhereNestedStaticVariableExpression()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE some_date > ADDDATE(NOW(), ?);', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('bindValue')
            ->with(1, 2, PDO::PARAM_INT);

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('some_date', VariableExpression::ADDDATE(Expression::NOW(), 2), Operator::GREATER)
            ->fetchAll();
    }

    public function testSelectWhereBool()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE active = ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('bindValue')
            ->with(1, true, PDO::PARAM_BOOL);

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('active', true)
            ->fetchAll();
    }

    public function testSelectGrouped()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users GROUP BY age;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::never())
            ->method('bindValue');

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->groupBy('age')
            ->fetchAll();
    }

    public function testSelectGroupedMultiple()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users GROUP BY age, gender;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::never())
            ->method('bindValue');

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->groupBy(['age', 'gender'])
            ->fetchAll();
    }

    public function testSelectWhereIn()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age IN (?, ?, ?);', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(3))
            ->method('bindValue')
            ->withConsecutive(
                [1, 19, PDO::PARAM_INT],
                [2, 29, PDO::PARAM_INT],
                [3, 39, PDO::PARAM_INT],
            );

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->whereIn('age', [19, 29, 39])
            ->fetchAll();
    }

    public function testSelectWhereInSubQuery()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with(
                'SELECT * FROM users WHERE company_id IN (SELECT id FROM companies WHERE sector = ?);',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
            ->willReturn($statement);

        $statement->expects(static::exactly(1))
            ->method('bindValue')
            ->withConsecutive([1, 'tech', PDO::PARAM_STR]);

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->whereIn('company_id', fn(Builder $b) => $b->select('id')->from('companies')->where('sector', 'tech'))
            ->fetchAll();
    }

    public function testSelectWhereNotIn()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age NOT IN (?, ?, ?);', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(3))
            ->method('bindValue')
            ->withConsecutive(
                [1, 19, PDO::PARAM_INT],
                [2, 29, PDO::PARAM_INT],
                [3, 39, PDO::PARAM_INT],
            );

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->whereNotIn('age', [19, 29, 39])
            ->fetchAll();
    }

    public function testSelectMultipleWhere()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ? AND gender = ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive([1, 25, PDO::PARAM_INT], [2, 'female', PDO::PARAM_STR]);

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->where('gender', 'female')
            ->fetchAll();
    }

    public function testSelectOrWhere()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ? OR age = ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive([1, 20, PDO::PARAM_INT], [2, 30, PDO::PARAM_INT]);

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 20)
            ->orWhere('age', 30)
            ->fetchAll();
    }

    public function testSelectNestedOrWhere()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with(
                'SELECT * FROM users WHERE gender = ? AND (age > ? OR age < ?) OR job IS ?;',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
            ->willReturn($statement);

        $statement->expects(static::exactly(4))
            ->method('bindValue')
            ->withConsecutive([1, 'female', PDO::PARAM_STR], [2, 20, PDO::PARAM_INT], [3, 30, PDO::PARAM_INT], [4, null, PDO::PARAM_NULL]);

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('gender', 'female')
            ->where(fn(Builder $b) => $b->where('age', 20, Operator::GREATER)->orWhere('age', 30, Operator::LESS))
            ->orWhereIsNull('job')
            ->fetchAll();
    }

    public function testSelectNestedOrWhereFirst()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE (age > ? AND age < ?) AND gender = ? OR job IS ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(4))
            ->method('bindValue')
            ->withConsecutive(
                [1, 20, PDO::PARAM_INT],
                [2, 30, PDO::PARAM_INT],
                [3, 'female', PDO::PARAM_STR],
                [4, null, PDO::PARAM_NULL]
            );

        $statement->expects(static::once())
            ->method('execute');
        $statement->expects(static::once())
            ->method('fetchAll');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where(fn(Builder $b) => $b->where('age', 20, Operator::GREATER)->where('age', 30, Operator::LESS))
            ->where('gender', 'female')
            ->orWhereIsNull('job')
            ->fetchAll();
    }

    public function testBindingNotAllowedError()
    {
        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage('Bindings with type resource are not allowed');
        $r = fopen('php://stdin', 'r');
        (new Builder($this->mockPDO()))
            ->setClassName('User')
            ->from('users')
            ->where('age', $r)
            ->fetch();
    }

    public function testWhereArgumentCountError()
    {
        static::expectException(\ArgumentCountError::class);
        static::expectExceptionMessage(
            sprintf(
                'Too few arguments to function %s::xorWhere(), 0 passed in %s on line %s and exactly 1 expected',
                Builder::class,
                __FILE__,
                __LINE__ + 7
            )
        );

        (new Builder($this->mockPDO()))
            ->setClassName('User')
            ->from('users')
            ->xorWhere();
    }

    public function joinArgumentCountErrorProvider(): \Generator
    {
        yield [[]];
        yield [['profiles']];
        yield [['profiles', 'profiles.user_id']];
    }

    /** @dataProvider joinArgumentCountErrorProvider */
    public function testJoinArgumentCountError(array $arguments)
    {
        static::expectException(\ArgumentCountError::class);
        static::expectExceptionMessage(
            sprintf(
                'Too few arguments to function %s::crossJoin(), %s passed in %s on line %s and exactly 3 expected',
                Builder::class,
                count($arguments),
                __FILE__,
                __LINE__ + 7
            )
        );

        (new Builder($this->mockPDO()))
            ->setClassName('User')
            ->from('users')
            ->crossJoin(...$arguments);
    }

    public function badMethodsProvider(): \Generator
    {
        yield ['orDuck', ['quacks']];
        yield ['whereTheDuck', ['quacks']];
        yield ['duckJoin', ['ducks', 'swans', 'birds']];
    }

    /** @dataProvider badMethodsProvider */
    public function testBadMethodCallException(string $method, array $arguments)
    {
        static::expectException(\BadMethodCallException::class);
        static::expectExceptionMessage(
            sprintf(
                'Call to undefined method %s::%s() in %s:%s',
                Builder::class,
                $method,
                __FILE__,
                __LINE__ + 7
            )
        );

        (new Builder($this->mockPDO()))
            ->setClassName('User')
            ->from('users')
            ->$method(...$arguments);
    }

    public function testMagicXorWhere()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ? XOR sector = ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive(
                [1, 25, PDO::PARAM_INT],
                [2, 'tech', PDO::PARAM_STR],
            );

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->xorWhere('sector', 'tech')
            ->fetch();
    }

    public function testMagicXorWhereWithOperator()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ? XOR sector != ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive(
                [1, 25, PDO::PARAM_INT],
                [2, 'tech', PDO::PARAM_STR],
            );

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->xorWhere('sector', 'tech', Operator::NOT_EQUALS)
            ->fetch();
    }

    public function testMagicXorWhereNot()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ? XOR sector != ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive(
                [1, 25, PDO::PARAM_INT],
                [2, 'tech', PDO::PARAM_STR],
            );

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->xorWhereNot('sector', 'tech')
            ->fetch();
    }

    public function testMagicXorWhereIsNull()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ? XOR sector IS ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive([1, 25, PDO::PARAM_INT], [2, null, PDO::PARAM_NULL]);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->xorWhereIsNull('sector')
            ->fetch();
    }

    public function testMagicXorWhereIsNotNull()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ? XOR sector IS NOT ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive([1, 25, PDO::PARAM_INT], [2, null, PDO::PARAM_NULL]);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->xorWhereIsNotNull('sector')
            ->fetch();
    }

    public function testMagicWhereNull()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ? AND sector IS ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive([1, 25, PDO::PARAM_INT], [2, null, PDO::PARAM_NULL]);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->whereIsNull('sector')
            ->fetch();
    }

    public function testMagicWhereLike()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ? AND sector LIKE ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive([1, 25, PDO::PARAM_INT], [2, '%tech%', PDO::PARAM_STR]);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->whereLike('sector', '%tech%')
            ->fetch();
    }

    public function testMagicWhereIn()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ? AND sector IN (?, ?);', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(3))
            ->method('bindValue')
            ->withConsecutive([1, 25, PDO::PARAM_INT], [2, '%tech%', PDO::PARAM_STR], [3, '%film%', PDO::PARAM_STR]);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->whereIn('sector', ['%tech%', '%film%'])
            ->fetch();
    }

    public function testMagicWhereNotIn()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ? AND sector NOT IN (?, ?);', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(3))
            ->method('bindValue')
            ->withConsecutive([1, 25, PDO::PARAM_INT], [2, '%tech%', PDO::PARAM_STR], [3, '%film%', PDO::PARAM_STR]);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->whereNotIn('sector', ['%tech%', '%film%'])
            ->fetch();
    }

    public function testMagicWhereLess()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ? AND comp < ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive([1, 25, PDO::PARAM_INT], [2, 25000, PDO::PARAM_INT]);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->whereLess('comp', 25000)
            ->fetch();
    }

    public function testMagicWhereGreater()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE age = ? AND comp > ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive([1, 25, PDO::PARAM_INT], [2, 25000, PDO::PARAM_INT]);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->whereGreater('comp', 25000)
            ->fetch();
    }

    public function testUpdate()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('UPDATE users SET job = ?, updated_at = NOW() WHERE id = ?;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive([1, 'Software Developer', PDO::PARAM_STR], [2, 12, PDO::PARAM_INT]);

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->table('users')
            ->where('id', 12)
            ->update([
                'job' => 'Software Developer',
                'updated_at' => Expression::NOW()
            ]);
    }

    public function testInsert()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('INSERT INTO users (age, gender, job, updated_at) VALUES (?, ?, ?, NOW());', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $pdo->expects(static::once())
            ->method('lastInsertId')
            ->willReturn('1');

        $statement->expects(static::exactly(3))
            ->method('bindValue')
            ->withConsecutive(
                [1, 25, PDO::PARAM_INT],
                [2, 'non-binary', PDO::PARAM_STR],
                [3, 'Software Developer', PDO::PARAM_STR],
            );

        $statement->expects(static::once())
            ->method('execute');

        $id = (new Builder($pdo))
            ->into('users')
            ->insert([
                'age' => 25,
                'gender' => 'non-binary',
                'job' => 'Software Developer',
                'updated_at' => Expression::NOW()
            ]);
        static::assertEquals(1, $id);
    }

    public function testInsertJSON()
    {
        $settings = [
            'key' => 'value',
            'nested' => [
                'something' => true
            ],
            'ttl' => 123
        ];

        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('INSERT INTO settings (content, updated_at) VALUES (?, NOW());', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('bindValue')
            ->withConsecutive(
                [1, json_encode($settings), PDO::PARAM_STR],
            );

        (new Builder($pdo))
            ->into('settings')
            ->insert([
                'content' => $settings,
                'updated_at' => Expression::NOW()
            ]);
    }

    public function testInsertIgnore()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('INSERT IGNORE INTO users (age, gender, job, updated_at) VALUES (?, ?, ?, now());', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(3))
            ->method('bindValue')
            ->withConsecutive(
                [1, 25, PDO::PARAM_INT],
                [2, 'non-binary', PDO::PARAM_STR],
                [3, 'Software Developer', PDO::PARAM_STR],
            );

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->setClassName('User')
            ->into('users')
            ->insertIgnore([
                'age' => 25,
                'gender' => 'non-binary',
                'job' => 'Software Developer',
                'updated_at' => new Expression('now()')
            ]);
    }

    public function testDelete()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('DELETE FROM users WHERE id = ? ORDER BY id ASC LIMIT 1;', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
            ->willReturn($statement);

        $statement->expects(static::exactly(1))
            ->method('bindValue')
            ->withConsecutive(
                [1, 1, PDO::PARAM_INT],
            );

        $statement->expects(static::once())
            ->method('execute');

        (new Builder($pdo))
            ->from('users')
            ->where('id', 1)
            ->orderByAsc('id')
            ->limit(1)
            ->delete();
    }

    public function testJoins()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with(
                'SELECT * FROM users LEFT JOIN companies AS c ON user.company_id = c.id LEFT OUTER JOIN sectors ON c.sector_id = sectors.id WHERE users.age <= ? AND sectors.name = ?;',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive(
                [1, 30, PDO::PARAM_INT],
                [2, 'tech', PDO::PARAM_STR],
            );

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->leftJoin('companies', 'user.company_id', 'c.id', Operator::EQUALS, 'c')
            ->leftOuterJoin('sectors', 'c.sector_id', 'sectors.id')
            ->where('users.age', 30, Operator::LESS_EQUALS)
            ->where('sectors.name', 'tech')
            ->fetchAll();
    }

    public function testCrossJoins()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with(
                'SELECT * FROM users CROSS JOIN companies ON user.company_id = companies.id CROSS JOIN sectors ON company.sector_id = sectors.id WHERE users.age <= ? AND sectors.name = ?;',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive(
                [1, 30, PDO::PARAM_INT],
                [2, 'tech', PDO::PARAM_STR],
            );

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->crossJoin('companies', 'user.company_id', 'companies.id')
            ->crossJoin('sectors', 'company.sector_id', 'sectors.id')
            ->where('users.age', 30, Operator::LESS_EQUALS)
            ->where('sectors.name', 'tech')
            ->fetchAll();
    }

    public function testRightJoins()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with(
                'SELECT * FROM users RIGHT JOIN companies ON user.company_id = companies.id RIGHT OUTER JOIN sectors ON company.sector_id = sectors.id WHERE users.age <= ? AND sectors.name = ?;',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive(
                [1, 30, PDO::PARAM_INT],
                [2, 'tech', PDO::PARAM_STR],
            );

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->rightJoin('companies', 'user.company_id', 'companies.id')
            ->rightOuterJoin('sectors', 'company.sector_id', 'sectors.id')
            ->where('users.age', 30, Operator::LESS_EQUALS)
            ->where('sectors.name', 'tech')
            ->fetchAll();
    }

    public function testJoinsWithOperators()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with(
                'SELECT * FROM users RIGHT JOIN companies ON user.company_id != companies.id RIGHT OUTER JOIN sectors ON company.sector_id < sectors.id WHERE users.age <= ? AND sectors.name = ?;',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive(
                [1, 30, PDO::PARAM_INT],
                [2, 'tech', PDO::PARAM_STR],
            );

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->rightJoin('companies', 'user.company_id', 'companies.id', Operator::NOT_EQUALS)
            ->rightOuterJoin('sectors', 'company.sector_id', 'sectors.id', Operator::LESS)
            ->where('users.age', 30, Operator::LESS_EQUALS)
            ->where('sectors.name', 'tech')
            ->fetchAll();
    }

    public function testSubQueryJoins()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with(
                'SELECT users.*, friends.count FROM users JOIN (SELECT user_id, COUNT(friend_id) AS count FROM friends WHERE friends.active = ? GROUP BY user_id) AS friends ON friends.user_id = users.id;',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('bindValue')
            ->with(1, true, PDO::PARAM_BOOL);

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->select(['users.*', 'friends.count'])
            ->join(
                fn(Builder $b) => $b
                    ->select(['user_id', 'COUNT(friend_id) AS count'])
                    ->from('friends')
                    ->as('friends')
                    ->where('friends.active', true)
                    ->groupBy('user_id'),
                'friends.user_id',
                'users.id'
            )
            ->fetchAll();
    }

    public function testJoinInWhere()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with(
                'SELECT users.*, friends.count FROM users JOIN friends ON friends.user_id = users.id WHERE friends.friend_id = users.id;',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
            ->willReturn($statement);

        $statement->expects(static::never())
            ->method('bindValue');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->select(['users.*', 'friends.count'])
            ->where(fn(Builder $b) => $b->join('friends', 'friends.user_id', 'users.id')->where('friends.friend_id', new Expression('users.id')))
            ->fetchAll();
    }

    public function testHaving()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with(
                'SELECT users.*, JSON_OBJECTAGG(preferences.key, preferences.value) as settings FROM users LEFT JOIN preferences ON preferences.user_id = users.id GROUP BY users.id HAVING settings->"$.notify" = ?;',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
            ->willReturn($statement);

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->select(['users.*', 'JSON_OBJECTAGG(preferences.key, preferences.value) as settings'])
            ->leftJoin('preferences', 'preferences.user_id', 'users.id')
            ->groupBy('users.id')
            ->having('settings->"$.notify"', 1)
            ->fetchAll();
    }

    public function testHavingNoPreferences()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with(
                'SELECT users.*, JSON_OBJECTAGG(preferences.key, preferences.value) as settings FROM users LEFT JOIN preferences ON preferences.user_id = users.id GROUP BY users.id HAVING settings = ?;',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('bindValue')
            ->with(1, '{}', PDO::PARAM_STR);

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->select(['users.*', 'JSON_OBJECTAGG(preferences.key, preferences.value) as settings'])
            ->leftJoin('preferences', 'preferences.user_id', 'users.id')
            ->groupBy('users.id')
            ->having('settings', new \stdClass())
            ->fetchAll();
    }

    public function testHavingCallableColumn()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with(
                'SELECT users.*, JSON_OBJECTAGG(preferences.key, preferences.value) as settings FROM users LEFT JOIN preferences ON preferences.user_id = users.id GROUP BY users.id HAVING (users.age > ? AND users.age < ?) OR settings->"$.notify" = ?;',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
            ->willReturn($statement);

        $statement->expects(static::exactly(3))
            ->method('bindValue')
            ->withConsecutive([1, 20, PDO::PARAM_INT], [2, 30, PDO::PARAM_INT], [3, 1, PDO::PARAM_INT]);

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->select(['users.*', 'JSON_OBJECTAGG(preferences.key, preferences.value) as settings'])
            ->leftJoin('preferences', 'preferences.user_id', 'users.id')
            ->groupBy('users.id')
            ->having(fn(Builder $b) => $b
                ->having('users.age', 20, Operator::GREATER)
                ->having('users.age', 30, Operator::LESS)
            )
            ->orHaving('settings->"$.notify"', 1)
            ->fetchAll();
    }

    public function testHavingCallableValue()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with(
                'SELECT users.*, JSON_OBJECTAGG(preferences.key, preferences.value) as settings FROM users LEFT JOIN preferences ON preferences.user_id = users.id GROUP BY users.id HAVING (users.age > ? AND users.age < ?) OR users.company_id IN (SELECT id FROM companies WHERE sector = ?);',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
            ->willReturn($statement);

        $statement->expects(static::exactly(3))
            ->method('bindValue')
            ->withConsecutive([1, 20, PDO::PARAM_INT], [2, 30, PDO::PARAM_INT], [3, 'tech', PDO::PARAM_STR]);

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->select(['users.*', 'JSON_OBJECTAGG(preferences.key, preferences.value) as settings'])
            ->leftJoin('preferences', 'preferences.user_id', 'users.id')
            ->groupBy('users.id')
            ->having(fn(Builder $b) => $b
                ->having('users.age', 20, Operator::GREATER)
                ->having('users.age', 30, Operator::LESS)
            )
            ->orHavingIn('users.company_id', fn(Builder $b) => $b->select('id')->from('companies')->where('sector', 'tech'))
            ->fetchAll();
    }
}
