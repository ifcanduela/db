<?php

use ifcanduela\db\Query;

class DeleteQueryTest extends PHPUnit\Framework\TestCase
{
    public function testFromIsRequired()
    {
        $q = Query::delete('users')->where(['id' => ['>', 3]]);

        $this->assertEquals('DELETE FROM users WHERE (id > :p_1)', $q->getSql());
        $this->assertEquals([
            ':p_1' => 3,
        ], $q->getParams());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testTableIsrequired()
    {
        $q = Query::delete()->where(['a' => 1]);
        $q->getSql();
    }
}
