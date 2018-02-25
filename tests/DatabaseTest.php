<?php

use ifcanduela\db\Database;
use ifcanduela\db\Query;

class DatabaseTest extends PHPUnit\Framework\TestCase
{
    public $testMysql = false;

    public function getDatabase()
    {
        return Database::sqlite(':memory:');
    }

    public function getSqliteDatabase()
    {
        $d = $this->getDatabase();

        $d->run('CREATE TABLE users (id INTEGER PRIMARY KEY)');
        $d->run('INSERT INTO users (id) VALUES (1)');
        $d->run('INSERT INTO users (id) VALUES (2)');
        $d->run('INSERT INTO users (id) VALUES (3)');

        return $d;
    }

    public function getMysqlDatabase()
    {
        return Database::mysql(
            '127.0.0.1',
            'world',
            'test',
            'test'
        );
    }

    public function testSqliteInstance()
    {
        $d = Database::sqlite(':memory:');

        $this->assertTrue($d instanceof Database);
    }

    public function testGetTables()
    {
        $d = $this->getSqliteDatabase();
        $tables = $d->tableNames();
        $this->assertEquals(['users'], $tables);
    }

    public function testGetTablesInMysql()
    {
        if (!$this->testMysql) {
            $this->markTestSkipped("MySQL tests disabled.'");
        }

        $d = $this->getMysqlDatabase();
        $tables = $d->tableNames();
        $this->assertEquals(['city', 'country', 'countrylanguage'], $tables);
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
        $d = $this->getSqliteDatabase();

        $users = $d->run('SELECT * FROM users');
        $this->assertCount(3, $users);

        $users = $d->run('SELECT * FROM users WHERE id = ?', [2]);
        $this->assertCount(1, $users);

        $users = $d->run('SELECT * FROM users WHERE id > :id', [1]);
        $this->assertCount(2, $users);
    }

    public function testGetRow()
    {
        $d = $this->getSqliteDatabase();

        $user = $d->row('SELECT * FROM users');
        $this->assertEquals('1', $user['id']);

        $user = $d->row('SELECT * FROM users WHERE id = ?', [2]);
        $this->assertEquals('2', $user['id']);

        $user = $d->row('SELECT * FROM users WHERE id > :id', [1]);
        $this->assertEquals('2', $user['id']);
    }

    public function testGetCell()
    {
        $d = $this->getSqliteDatabase();

        $userId = $d->cell('SELECT * FROM users');
        $this->assertEquals('1', $userId);

        $userId = $d->cell('SELECT * FROM users WHERE id = ?', [2]);
        $this->assertEquals('2', $userId);

        $userId = $d->cell('SELECT * FROM users WHERE id > :id', [1], 'id');
        $this->assertEquals('2', $userId);
    }
}
