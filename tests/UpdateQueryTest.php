<?php

use ifcanduela\db\Query;
use function ifcanduela\db\raw;

class UpdateQueryTest extends PHPUnit\Framework\TestCase
{
    public function testFromIsRequired()
    {
        $q = Query::update('users')->set(['active' => false, 'timestamp' => raw('NOW()')])->where(['id' => ['>', 3]]);

        $this->assertEquals('UPDATE users SET active = :p_1, timestamp = NOW() WHERE (id > :p_2)', $q->getSql());
        $this->assertEquals([
            ':p_1' => false,
            ':p_2' => 3,
        ], $q->getParams());
    }

    public function testTableIsRequired()
    {
        $this->expectException(RuntimeException::class);
        $q = Query::update()->set(['a' => 1]);
        $q->getSql();
    }

    public function testSetIsRequired()
    {
        $this->expectException(RuntimeException::class);
        $q = Query::update()->table('users');
        $q->getSql();
    }
}
