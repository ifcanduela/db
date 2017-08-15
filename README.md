# db: Query Builder and Connection manager

An easy-to-use database connection manager and query builder for SQLite and MySQL.

## Getting started

Install using [Composer](getcomposer.org).

## Connecting to a database

The `ifcanduela\db\Database` class extends PDO, but includes two static methods to connect
to MySQL and SQLite:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use ifcanduela\db\Database;

$sqlite = Database::sqlite($filename, $options);
$mysql  = Database::mysql($host, $dbname, $user, $password, $options);
```

The arguments match those in the [PDO constructor](http://php.net/manual/en/pdo.construct.php).

The following options are set by default when using the static factories go create a connection:

- PDO will throw exceptions on error.
- Results will be returned as associative arrays.
- Prepared statements will **not** be emulated.

## Query builder

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use ifcanduela\db\Query;

$query = Query::select()->columns('users.*')->from('users')->leftJoin('profiles', ['users.id' => 'profiles.user_id'])->where(['status' => ['<>', 1]])->orderBy('created DESC', 'username')->limit(1, 2);

echo $query; // or $query->getSql();
// SELECT users.* 
// FROM users LEFT JOIN profiles ON users.id = profiles.user_id 
// WHERE status <> :_param_1
// ORDER BY created DESC, username
// LIMIT 2, 1;
```

You can get the parameters for the prepared statement by calling `getParams()` on the `$query`
object.

## Running queries

When you have a connection and have built a query, you can call the `run` method on the connection
to run a query:

```php
$sqlite->run($query);
```

Which is equivalent to this:

```php
$sqlite->query($query->getSql(), $query->getParams());
```
