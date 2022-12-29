<?php

use MrMadClown\Mnemosyne\Builder;
use MrMadClown\Mnemosyne\Direction;
use MrMadClown\Mnemosyne\Expression;
use MrMadClown\Mnemosyne\Operator;
use MrMadClown\Mnemosyne\VariableExpression;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    public function testSelectAll()
    {
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users;')
            ->willReturn($this->mockStatement());

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->fetchAll();
    }

    public function testCountAll()
    {
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT COUNT(*) FROM users;')
            ->willReturn($this->mockStatement());

        (new Builder($pdo))
            ->setClassName('User')
            ->count()
            ->from('users')
            ->fetchAll();
    }

    public function testSelectAllLimit()
    {
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users LIMIT 10;')
            ->willReturn($this->mockStatement());

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->limit(10)
            ->fetchAll();
    }

    public function testSelectSingleColumn()
    {
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT id FROM users;')
            ->willReturn($this->mockStatement());

        (new Builder($pdo))
            ->setClassName('User')
            ->select('id')
            ->from('users')
            ->fetchAll();
    }

    public function testSelectOderByMultiple()
    {
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users ORDER BY id DESC, age ASC;')
            ->willReturn($this->mockStatement());

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
            ->with('SELECT id, name FROM users;')
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
            ->with('SELECT * FROM users WHERE age = ?;')
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('bindValue')
            ->with(1, 25, PDO::PARAM_INT);

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('age', 25)
            ->fetchAll();
    }

    public function testSelectWhereExpression()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE updated_at > DATE(now());')
            ->willReturn($statement);

        $statement->expects(static::never())
            ->method('bindValue');

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
            ->with('SELECT * FROM users WHERE updated_at > DATE(NOW());')
            ->willReturn($statement);

        $statement->expects(static::never())
            ->method('bindValue');

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
            ->with('SELECT * FROM users WHERE hashed_id = crc32(?);')
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('bindValue')
            ->with(1, 'my-unique-user-id', PDO::PARAM_STR);

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
            ->with('SELECT * FROM users WHERE hashed_id = crc32(floor(?));')
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('bindValue')
            ->with(1, 13.5, PDO::PARAM_STR);

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
            ->with('SELECT * FROM users WHERE some_date > ADDDATE(NOW(), ?);')
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('bindValue')
            ->with(1, 2, PDO::PARAM_INT);

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
            ->with('SELECT * FROM users WHERE active = ?;')
            ->willReturn($statement);

        $statement->expects(static::once())
            ->method('bindValue')
            ->with(1, true, PDO::PARAM_BOOL);

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
            ->with('SELECT * FROM users GROUP BY age;')
            ->willReturn($statement);

        $statement->expects(static::never())
            ->method('bindValue');

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
            ->with('SELECT * FROM users GROUP BY age, gender;')
            ->willReturn($statement);

        $statement->expects(static::never())
            ->method('bindValue');

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
            ->with('SELECT * FROM users WHERE age IN (?, ?, ?);')
            ->willReturn($statement);

        $statement->expects(static::exactly(3))
            ->method('bindValue')
            ->withConsecutive(
                [1, 19, PDO::PARAM_INT],
                [2, 29, PDO::PARAM_INT],
                [3, 39, PDO::PARAM_INT],
            );

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
            ->with('SELECT * FROM users WHERE company_id IN (SELECT id FROM companies WHERE sector = ?);')
            ->willReturn($statement);

        $statement->expects(static::exactly(1))
            ->method('bindValue')
            ->withConsecutive([1, 'tech', PDO::PARAM_STR]);

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
            ->with('SELECT * FROM users WHERE age NOT IN (?, ?, ?);')
            ->willReturn($statement);

        $statement->expects(static::exactly(3))
            ->method('bindValue')
            ->withConsecutive(
                [1, 19, PDO::PARAM_INT],
                [2, 29, PDO::PARAM_INT],
                [3, 39, PDO::PARAM_INT],
            );

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
            ->with('SELECT * FROM users WHERE age = ? AND gender = ?;')
            ->willReturn($statement);


        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive([1, 25, PDO::PARAM_INT], [2, 'female', PDO::PARAM_STR]);

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
            ->with('SELECT * FROM users WHERE age = ? OR age = ?;')
            ->willReturn($statement);


        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive([1, 20, PDO::PARAM_INT], [2, 30, PDO::PARAM_INT]);

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
            ->with('SELECT * FROM users WHERE gender = ? AND (age > ? AND age < ?) OR job IS ?;')
            ->willReturn($statement);

        $statement->expects(static::exactly(4))
            ->method('bindValue')
            ->withConsecutive([1, 'female', PDO::PARAM_STR], [2, 20, PDO::PARAM_INT], [3, 30, PDO::PARAM_INT], [4, null, PDO::PARAM_NULL]);

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where('gender', 'female')
            ->where(fn(Builder $b) => $b->where('age', 20, Operator::GREATER)->where('age', 30, Operator::LESS))
            ->orWhereIsNull('job')
            ->fetchAll();
    }

    public function testSelectNestedOrWhereFirst()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT * FROM users WHERE (age > ? AND age < ?) AND gender = ? OR job IS ?;')
            ->willReturn($statement);

        $statement->expects(static::exactly(4))
            ->method('bindValue')
            ->withConsecutive(
                [1, 20, PDO::PARAM_INT],
                [2, 30, PDO::PARAM_INT],
                [3, 'female', PDO::PARAM_STR],
                [4, null, PDO::PARAM_NULL]
            );

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->where(fn(Builder $b) => $b->where('age', 20, Operator::GREATER)->where('age', 30, Operator::LESS))
            ->where('gender', 'female')
            ->orWhereIsNull('job')
            ->fetchAll();
    }

    public function testArgumentCountError()
    {
        static::expectException(\ArgumentCountError::class);
        static::expectExceptionMessage(
            sprintf(
                'Too few arguments to function %s::xorWhere(), 0 passed in %s on line 454 and exactly 1 expected',
                Builder::class,
                __FILE__
            )
        );

        (new Builder($this->mockPDO()))
            ->setClassName('User')
            ->from('users')
            ->xorWhere();
    }

    public function testBadMethodCallException()
    {
        static::expectException(\BadMethodCallException::class);
        static::expectExceptionMessage(
            sprintf(
                'Call to undefined method %s::orWhat() in %s:471',
                Builder::class,
                __FILE__
            )
        );

        (new Builder($this->mockPDO()))
            ->setClassName('User')
            ->from('users')
            ->orWhat('id');
    }

    public function testUpdate()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('UPDATE users SET job = ?, updated_at = NOW() WHERE id = ?;')
            ->willReturn($statement);

        $statement->expects(static::exactly(2))
            ->method('bindValue')
            ->withConsecutive([1, 'Software Developer', PDO::PARAM_STR], [2, 12, PDO::PARAM_INT]);

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
            ->with('INSERT INTO users (age, gender, job, updated_at) VALUES (?, ?, ?, NOW());')
            ->willReturn($statement);

        $statement->expects(static::exactly(3))
            ->method('bindValue')
            ->withConsecutive(
                [1, 25, PDO::PARAM_INT],
                [2, 'non-binary', PDO::PARAM_STR],
                [3, 'Software Developer', PDO::PARAM_STR],
            );

        (new Builder($pdo))
            ->into('users')
            ->insert([
                'age' => 25,
                'gender' => 'non-binary',
                'job' => 'Software Developer',
                'updated_at' => Expression::NOW()
            ]);
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
            ->with('INSERT INTO settings (content, updated_at) VALUES (?, NOW());')
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
            ->with('INSERT IGNORE INTO users (age, gender, job, updated_at) VALUES (?, ?, ?, now());')
            ->willReturn($statement);

        $statement->expects(static::exactly(3))
            ->method('bindValue')
            ->withConsecutive(
                [1, 25, PDO::PARAM_INT],
                [2, 'non-binary', PDO::PARAM_STR],
                [3, 'Software Developer', PDO::PARAM_STR],
            );

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
            ->with('DELETE FROM users WHERE id = ? ORDER BY id ASC LIMIT 1;')
            ->willReturn($statement);

        $statement->expects(static::exactly(1))
            ->method('bindValue')
            ->withConsecutive(
                [1, 1, PDO::PARAM_INT],
            );

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
            ->with('SELECT * FROM users LEFT JOIN companies ON user.company_id = companies.id LEFT JOIN sectors ON company.sector_id = sectors.id WHERE users.age <= ? AND sectors.name = ?;')
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
            ->leftJoin('companies', 'user.company_id', 'companies.id')
            ->leftJoin('sectors', 'company.sector_id', 'sectors.id')
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
            ->with('SELECT users.*, friends.count FROM users JOIN (SELECT user_id, COUNT(friend_id) AS count FROM friends GROUP BY user_id) AS friends ON friends.user_id = users.id;')
            ->willReturn($statement);

        $statement->expects(static::never())
            ->method('bindValue');

        (new Builder($pdo))
            ->setClassName('User')
            ->from('users')
            ->select(['users.*', 'friends.count'])
            ->join(fn(Builder $b) => $b->select(['user_id', 'COUNT(friend_id) AS count'])->from('friends')->as('friends')->groupBy('user_id'), 'friends.user_id', 'users.id')
            ->fetchAll();
    }

    public function testJoinInWhere()
    {
        $statement = $this->mockStatement();
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('SELECT users.*, friends.count FROM users JOIN friends ON friends.user_id = users.id WHERE friends.friend_id = users.id;')
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
            ->with('SELECT users.*, JSON_OBJECTAGG(preferences.key, preferences.value) as settings FROM users LEFT JOIN preferences ON preferences.user_id = users.id GROUP BY users.id HAVING settings->"$.notify" = ?;')
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

    private function mockPDO(): MockObject&PDO
    {
        return $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function mockStatement(): MockObject&PDOStatement
    {
        return $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
