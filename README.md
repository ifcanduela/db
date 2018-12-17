# db: Query Builder and Connection manager

An easy-to-use database connection manager and query builder for SQLite and MySQL.

## Getting started

Install using [Composer](https://getcomposer.org).

## Connecting to a database

The `ifcanduela\db\Database` class extends PDO, but includes two static methods to connect
to MySQL and SQLite:

```php
require __DIR__ . '/vendor/autoload.php';

use ifcanduela\db\Database;

$sqlite = Database::sqlite($filename, $options);
$mysql  = Database::mysql($host, $dbname, $user, $password, $options);
```

The arguments match those in the [PDO constructor](http://php.net/manual/en/pdo.construct.php).

The following options are set by default when using the static factories to create a connection:

- PDO will throw exceptions on error.
- Results will be returned as associative arrays.
- Prepared statements will **not** be emulated.

### Create a conection using an array

Connections can also be created using an array:

```php
$mysql = Database::fromArray([
        'engine' => 'mysql',
        'host' => '127.0.0.1',
        'name' => 'some_database',
        'user' => 'some_username',
        'pass' => 'some_password',
    ]);

$sqlite = Database::fromArray([
        'engine' => 'sqlite',
        'file' => './db.sqlite',
    ]);

```

## Query builder

```php
require __DIR__ . '/vendor/autoload.php';

use ifcanduela\db\Query;

$query = Query::select()
    ->columns('users.*')
    ->from('users')
    ->leftJoin('profiles', ['users.id' => 'profiles.user_id'])
    ->where(['status' => ['<>', 1]])
    ->orderBy('created DESC', 'username')
    ->limit(1, 2);

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

## Logging queries

Queries run through the run() method can be logged using an object implementing `LoggerInterface`.
The query log entries use the `Logger::INFO` level. For example, using Monolog:

```php
use ifcanduela\db\Database;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('Query log');
$file_handler = new StreamHandler('queries.log', Logger::INFO);
$logger->pushHandler($file_handler);

$db = Database::sqlite(':memory');
$db->setLogger($logger);

$db->run('SELECT 1');
```

## Query builder API

### Select queries

```php
Query::select(string ...$field)
    ->distinct(bool $enable = true)
    ->columns(string ...$column)
    ->from(string ...$table)
    ->join(string $table, array $on)
    ->innerJoin(string $table, array $on)
    ->leftJoin(string $table, array $on)
    ->leftOuterJoin(string $table, array $on)
    ->rightJoin(string $table, array $on)
    ->outerJoin(string $table, array $on)
    ->fullOuterJoin(string $table, array $on)
    ->where(array $conditions)
    ->andWhere(array $conditions)
    ->orWhere(array $conditions)
    ->groupBy(string ...$field)
    ->having(array $conditions)
    ->andHaving(array $conditions)
    ->orHaving(array $conditions)
    ->orderBy(string ...$field)
    ->limit(int $limit, int $offset = null)
    ->offset(int $offset)
    ->getSql()
    ->getParams()
```

There is also a `Query::count()`  method that will select a `COUNT(*)` column 
automatically.

### Insert queries

```php
Query::insert(string $table = null)
    ->table(string $table)
    ->into(string $table)
    ->values(array ...$values)
    ->getSql()
    ->getParams()
```

### Update queries

```php
Query::update(string $table = null)
    ->table(string $table)
    ->set(array $values)
    ->where(array $conditions)
    ->andWhere(array $conditions)
    ->orWhere(array $conditions)
    ->getSql()
    ->getParams()
```

### Delete queries

```php
Query::delete(string $table = null)
    ->table(string $table)
    ->where(array $conditions)
    ->andWhere(array $conditions)
    ->orWhere(array $conditions)
    ->getSql()
    ->getParams()
```

### Specifying conditions

Building conditions is accomplished by using the `where()`, `andWhere()` and `orWhere()` 
methods (or their grouping equivalents, `having()`, `andHaving()` and `orHaving()`). 
Conditions must be associative arrays, where keys are expected to be the column names 
in the comparison and the left-side value are values or indexed arrays of operator and 
value.

Values will be converted to prepared statement parameters unless you use the 
`ifcanduela\db\qi()` function on them.

An example of a select query with multiple conditions would be this:

```php
$q = Query::select();

$q->columns('id', 'name', 'age');
$q->from('users');
$q->where(['id' => 1]);
$q->orWhere(['id' => 3]);
$q->andWhere(['age' => ['>', 18]]);
$q->orderBy('age DESC');
```

The resulting SQL will be similar to the following snippet:

```sql
SELECT id, name, age 
FROM users 
WHERE (id = :p_1 OR id = :p_2) AND age > :p_3
ORDER BY age DESC
```

And the parameters array would look like this:

```php
[
    ":p_1" => 1,
    ":p_2" => 3,
    ":p_3" => 18,
]
```

#### Complex conditions

If using the `where()` methods is confusing or insufficient, you can use simple arrays
to specify nested conditions:

```php
$q = Query::select()->where([
        'AND',
        'a' => 1,
        'b' => 2,
        [
            'OR',
            'c' => 3,
            'd' => 4,
        ]
    ]);
```

Which will result in something like this:

```sql
SELECT *
FROM users 
WHERE a = :p_1 AND b = :p_2 AND (c = :p_3 OR d = :p_4)
```

## License

[MIT](LICENSE).
