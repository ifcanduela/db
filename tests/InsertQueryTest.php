<?php

use ifcanduela\db\Query;

class InsertQueryTest extends PHPUnit\Framework\TestCase
{
    public function testBasicInsertQuery()
    {
        $row = 1;

        $q = Query::insert('users')->values(
                ['active' => false, 'timestamp' => $row++],
                ['active' => false, 'timestamp' => $row++],
                ['active' => false, 'timestamp' => $row++],
                ['active' => false, 'timestamp' => $row]
        );

        $this->assertEquals('INSERT INTO users (active, timestamp) VALUES (:p_1, :p_2), (:p_3, :p_4), (:p_5, :p_6), (:p_7, :p_8)', $q->getSql());
        $this->assertEquals([
            ':p_1' => false,
            ':p_2' => 1,
            ':p_3' => false,
            ':p_4' => 2,
            ':p_5' => false,
            ':p_6' => 3,
            ':p_7' => false,
            ':p_8' => 4,
        ], $q->getParams());
    }

    public function testTableIsrequired()
    {
        $this->expectException(\RuntimeException::class);
        $q = Query::insert()->values(['a' => 1]);
        $q->getSql();
    }

    public function testValuesIsrequired()
    {
        $this->expectException(\RuntimeException::class);
        $q = Query::insert()->into('users');
        $q->getSql();
    }
}
