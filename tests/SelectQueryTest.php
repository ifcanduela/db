<?php

use ifcanduela\db\Query;

use function ifcanduela\db\qi;

class SelectQueryTest extends PHPUnit\Framework\TestCase
{
    public function testFromIsRequired()
    {
        $this->expectException(RuntimeException::class);
        $q = Query::select();
        $q->getSql();
    }

    public function testBasicQuery()
    {
        $q = Query::select();

        $q->columns('id', 'name', 'age');
        $q->from('users');
        $q->where([
                'id' => 1,
            ]);
        $q->orWhere(['id' => 3]);
        $q->andWhere([
                'age' => ['>', 18],
            ]);
        $q->orderBy('age DESC');

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT id, name, age FROM users WHERE (((id = :p_1) OR (id = :p_2)) AND (age > :p_3)) ORDER BY age DESC';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([
            ':p_1' => 1,
            ':p_2' => 3,
            ':p_3' => 18,
        ], $params);
    }

    public function testDistinct()
    {
        $q = Query::select('id', 'name', 'age')->distinct();

        $q->from('users');

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT DISTINCT id, name, age FROM users';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([], $params);
    }

    public function testJoin()
    {
        $q = Query::select();

        $q->from('users');
        $q->join('profiles', ['users.last_name' => 'profiles.last_name']);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users JOIN profiles ON (users.last_name = profiles.last_name)';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([], $params);

        ///

        $q = Query::select();

        $q->from('users');
        $q->innerJoin('profiles', ['users.last_name' => 'profiles.last_name']);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users INNER JOIN profiles ON (users.last_name = profiles.last_name)';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([], $params);

        ///

        $q = Query::select();

        $q->from('users');
        $q->outerJoin('profiles', ['users.last_name' => 'profiles.last_name']);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users OUTER JOIN profiles ON (users.last_name = profiles.last_name)';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([], $params);
    }

    public function testLeftJoin()
    {
        $q = Query::select();

        $q->from('users');
        $q->leftJoin('profiles', ['users.last_name' => 'profiles.last_name']);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users LEFT JOIN profiles ON (users.last_name = profiles.last_name)';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([], $params);

        ///

        $q = Query::select();

        $q->from('users');
        $q->leftOuterJoin('profiles', ['users.last_name' => 'profiles.last_name']);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users LEFT OUTER JOIN profiles ON (users.last_name = profiles.last_name)';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([], $params);
    }

    public function testRightJoin()
    {
        $q = Query::select();

        $q->from('users');
        $q->rightJoin('profiles', ['users.last_name' => 'profiles.last_name']);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users RIGHT JOIN profiles ON (users.last_name = profiles.last_name)';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([], $params);
    }

    public function testFullOuterJoin()
    {
        $q = Query::select();

        $q->from('users');
        $q->fullOuterJoin('pets', ['profiles.id' => 'pets.profile.id']);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users FULL OUTER JOIN pets ON (profiles.id = pets.profile.id)';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([], $params);
    }

    public function testConditionBetween()
    {
        $q = Query::select();

        $q->from('users');
        $q->where([
                'id' => ['between', 1, 99],
            ]);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users WHERE (id BETWEEN :p_1 AND :p_2)';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([
            ':p_1' => 1,
            ':p_2' => 99,
        ], $params);
    }

    public function testConditionLike()
    {
        $q = Query::select();

        $q->from('users');
        $q->where([
                'name' => ['like', 'Igor%'],
            ]);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users WHERE (name LIKE :p_1)';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([
            ':p_1' => 'Igor%',
        ], $params);
    }

    public function testConditionIn()
    {
        $q = Query::select();

        $q->from('users');
        $q->where([
                'id' => ['not in', [1, 2, 3, 4, 5]],
            ]);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users WHERE (id NOT IN (:p_1, :p_2, :p_3, :p_4, :p_5))';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([
            ':p_1' => 1,
            ':p_2' => 2,
            ':p_3' => 3,
            ':p_4' => 4,
            ':p_5' => 5,
        ], $params);
    }

    public function testConditionIsNull()
    {
        $q = Query::select();

        $q->from('users');
        $q->where([
                'id' => ['is', null],
            ]);

        $params = $q->getParams();
        $sql = $q->getSql();

        $expected = 'SELECT * FROM users WHERE (id IS NULL)';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([], $params);
    }

    public function testGroupByHaving()
    {
        $q = Query::select();

        $q->from('users');
        $q->groupBy('last_name');
        $q->having(['age' => ['>', 18]]);
        $q->orHaving(['age' => ['<', 3]]);
        $q->andHaving(['id' => ['<>', 1]]);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users GROUP BY last_name HAVING (((age > :p_1) OR (age < :p_2)) AND (id <> :p_3))';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([
            ':p_1' => 18,
            ':p_2' => 3,
            ':p_3' => 1,
        ], $params);
    }

    public function testOrderBy()
    {
        $q = Query::select();

        $q->from('users');
        $q->orderBy('alpha', 'beta');

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users ORDER BY alpha, beta';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([], $params);
    }

    public function testLimitAndOffset()
    {
        $q = Query::select();

        $q->from('users');
        $q->limit(22);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users LIMIT :p_1';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([':p_1' => 22], $params);

        $q = Query::select();

        $q->from('users');
        $q->offset(5);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users LIMIT :p_1, :p_2';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([
                ':p_1' => 5,
                ':p_2' => PHP_INT_MAX,
            ], $params);

        $q = Query::select();

        $q->from('users');
        $q->limit(1, 5);

        $sql = $q->getSql();
        $params = $q->getParams();

        $expected = 'SELECT * FROM users LIMIT :p_1, :p_2';

        $this->assertEquals($expected, $sql);
        $this->assertEquals([
                ':p_1' => 5,
                ':p_2' => 1,
            ], $params);
    }

    public function testToString()
    {
        $q = Query::select()->from('users')->where(['id' => ['>', 1]]);
        $sql = (string) $q;

        $this->assertEquals('SELECT * FROM users WHERE (id > :p_1)', $sql);
    }

    public function testSubquery()
    {
        $q1 = Query::select()->from('users')->where(['id' => ['>', 1]]);
        $q2 = Query::select()->from("($q1)")->where(['age' => 3]);

        $sql = $q2->getSql();

        $this->assertEquals('SELECT * FROM (SELECT * FROM users WHERE (id > :p_1)) WHERE (age = :p_1)', $sql);
    }

    public function testCountQuery()
    {
        $q1 = Query::count()->from('users')->where(['id' => ['>', 1]]);

        $sql = $q1->getSql();

        $this->assertEquals('SELECT COUNT(*), * FROM users WHERE (id > :p_1)', $sql);
    }

    public function testQuoteIdentifier()
    {
        $this->assertEquals('"username"', qi('username'));
        $this->assertEquals('`users`', qi('users', '`'));
        $this->assertEquals('users', qi('users', ''));
        $this->assertEquals('[users]', qi('users', '[', ']'));
        $this->assertEquals('"users"."name" AS "username"', qi('users.name AS username'));
        $this->assertEquals('`users`.`name` `username`', qi('users.name username', '`'));
        $this->assertEquals('users.name username', qi('users.name username', ''));
        $this->assertEquals('[users].[name] AS [username]', qi('users.name AS username', '[', ']'));
    }

    public function testSelectWhereIsNullWithLimit()
    {
        $q = Query::select('users.*')->from('users')->where(['id' => null])->limit(1);
        $sql = $q->getSql();
        $expect = "SELECT users.* FROM users WHERE (id IS NULL) LIMIT :p_1";

        $this->assertEquals($expect, $sql);
    }

    public function testSelectWithComplexArrayCondition()
    {
        $q = Query::select()->from('users')->where([
            'AND',
            'a' => 1,
            'b' => 2,
            [
                'OR',
                'c' => 3,
                'd' => 4,
            ]
        ]);

        $q2 = Query::select()->from('users')
            ->where(['c' => 3])
            ->orWhere(['d' => 4])
            ->andWhere([
                'a' => 1,
                'b' => 2,
            ]);

        $sql = $q->getSql();
        $sql2 = $q2->getSql();

        $expect = "SELECT * FROM users WHERE (a = :p_1 AND b = :p_2 AND (c = :p_3 OR d = :p_4))";
        $expect2 = "SELECT * FROM users WHERE (((c = :p_1) OR (d = :p_2)) AND (a = :p_3 AND b = :p_4))";

        $this->assertEquals($expect, $sql);
        $this->assertEquals($expect2, $sql2);
    }
}
