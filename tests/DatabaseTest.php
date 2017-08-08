<?php

use ifcanduela\db\Database;
use ifcanduela\db\Query;

class DatabaseTest extends PHPUnit\Framework\TestCase
{
    public function getDatabase()
    {
        return Database::sqlite(':memory:');
    }

    public function getInitializedDatabase()
    {
        $d = $this->getDatabase();

        $d->run('CREATE TABLE users (id INTEGER PRIMARY KEY)');
        $d->run('INSERT INTO users (id) VALUES (1)');
        $d->run('INSERT INTO users (id) VALUES (2)');
        $d->run('INSERT INTO users (id) VALUES (3)');

        return $d;
    }

    public function testSqliteInstance()
    {
        $d = Database::sqlite(':memory:');

        $this->assertTrue($d instanceof Database);
    }

    public function testTableExists()
    {
        $d = $this->getDatabase();

        $this->assertFalse($d->tableExists('users'));

        $d->run('CREATE table users (id INTEGER PRIMARY KEY)');
        $this->assertTrue($d->tableExists('users'));
    }

    public function testQuery()
    {
        $d = $this->getInitializedDatabase();

        $users = $d->run('SELECT * FROM users');
        $this->assertCount(3, $users);

        $users = $d->run('SELECT * FROM users WHERE id = ?', [2]);
        $this->assertCount(1, $users);

        $users = $d->run('SELECT * FROM users WHERE id > :id', [1]);
        $this->assertCount(2, $users);
    }
}
