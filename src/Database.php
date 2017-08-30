<?php

namespace ifcanduela\db;

use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;

/**
 * Wrapper class for PDO database connections.
 *
 * Uses the following options by default:
 *
 * - Throw exceptions on error
 * - Return associative arrays for tuples
 * - Do not emulate prepared statements
 *
 * The above options are only set when using the two static factory methods.
 */
class Database extends PDO
{
    /** @var string */
    private $databaseType;

    /** @var bool */
    private $isWritable = true;

    /** @var Logger */
    private $logger;

    /** @var array Default PDO options set by the factory methods */
    private static $defaultOptions = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    /**
     * Create a database connection to SQLite ot MySQL, based on a configuration array.
     *
     * Array keys are as follows:
     *
     * engine  | required |             | Either 'mysql' or 'sqlite
     * file    | required | SQLite only | Path and filename or ':memory:'
     * host    | required | MySQL only  | MySQL host name or IP address
     * name    | required | MySQL only  | Schema name
     * user    | optional | MySQL only  | Username
     * pass    | optional | MySQL only  | Password
     * options | optional |             | Additional PDO connection options
     *
     * @param array $config
     * @return \ifcanduela\db\Database|null
     */
    public static function fromArray(array $config)
    {
        if (!isset($config['engine'])) {
            throw new \InvalidArgumentException("Missing engine");
        }

        if (!in_array(strtolower($config['engine']), ['sqlite', 'mysql'])) {
            throw new \InvalidArgumentException("Unsupported engine {$config['engine']}");
        }

        if ($config['engine'] === 'sqlite') {
            return static::sqlite(
                $config['file'],
                $config['options'] ?? []
            );
        }

        if ($config['engine'] === 'mysql') {
            return static::mysql(
                    $config['host'],
                    $config['name'],
                    $config['user'] ?? null,
                    $config['pass'] ?? null,
                    $config['options'] ?? []
                );
        }

        return null;
    }

    /**
     * Factory for a Sqlite connection.
     *
     * @param  string $file
     * @param  array  $options
     * @return static
     */
    public static function sqlite(string $file, array $options = [])
    {
        $options = array_replace(static::$defaultOptions, $options);
        $instance = new static("sqlite:{$file}", null, null, $options);
        $instance->databaseType = 'sqlite';
        $instance->isWritable = ($file === ':memory:') || (is_writable(dirname($file)) && is_writable($file));

        return $instance;
    }

    /**
     * Factory for a MySQL connection.
     *
     * @param  string      $host
     * @param  string      $dbname
     * @param  string|null $user
     * @param  string|null $password
     * @param  array       $options
     * @return static
     */
    public static function mysql(string $host, string $dbname, string $user = null, string $password = null, array $options = [])
    {
        $options = array_replace(static::$defaultOptions, $options);
        $instance = new static("mysql:host={$host};dbname=${dbname}", $user, $password, $options);
        $instance->databaseType = 'mysql';

        return $instance;
    }

    /**
     * Check if the database is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->isWritable;
    }

    /**
     * Run a query using a prepared statement and return an appropriate value.
     *
     * The return value is an array of rows for SELECT statements and a number of affected rows
     * for any other type of statement.
     *
     * @param string|Query $sql
     * @param array $params
     * @param boolean $returnStatement
     * @param int $fetchMode
     * @return array|int|PDOStatement
     */
    public function run($sql, array $params = [], bool $returnStatement = false, int $fetchMode = PDO::FETCH_ASSOC)
    {
        if ($sql instanceof Query) {
            $params = $sql->getParams();
            $sql = $sql->getSql();
        }

        $stm = $this->prepare($sql);
        $stm->execute($params);
        $this->logQuery($sql, $params);

        if ($returnStatement) {
            return $stm;
        } elseif (substr(trim($sql), 0, 6) === 'SELECT') {
            return $stm->fetchAll($fetchMode);
        } else {
            return $stm->rowCount();
        }
    }

    /**
     * Find out if the database contains a table.
     *
     * @param string $tableName Table name
     * @return boolean True if the table exists, false otherwise
     */
    public function tableExists(string $tableName)
    {
        try {
            $sql = "SELECT 1 FROM {$tableName} LIMIT 1";
            $this->prepare($sql);
            $this->logQuery("[PREPARE ONLY] $sql");
            return true;
        } catch (PDOException $e) {}

        return false;
    }

    /**
     * Finds the Primary Key fields of a table.
     *
     * The most common return is a string with the name of the primary key
     * column (for example, "id"). If the primary key is composite, this
     * method will return all primary keys in a comma-separated string, except
     * if the second parameter is specified as true, in which case the return
     * will be an array.
     *
     * @param string $table Name of the table in the database
     * @param bool $asArray Return multiple keys as an array (default is true)
     * @return mixed A comma-separated string with the primary key fields or an
     *               array if $asArray is true
     */
    public function getPrimaryKeys(string $table, bool $asArray = false)
    {
        $pk = [];

        if ($this->databaseType === 'mysql') {
            $sql = "SHOW COLUMNS FROM {$table}";
            $primaryKeyIndex = 'Key';
            $primaryKeyValue = 'PRI';
            $tableNameIndex = 'Field';
            $stm = $this->query($sql);
            $r = $stm->fetchAll();
        } elseif ($this->databaseType === 'sqlite') {
            $sql = "PRAGMA table_info({$table})";
            $primaryKeyIndex = 'pk';
            $primaryKeyValue = 1;
            $tableNameIndex = 'name';
            $stm = $this->query($sql);
            $r = $stm->fetchAll();
        } else {
            throw new \RuntimeException("Unsupported database type: '{$this->databaseType}'");
        }

        $this->logQuery($sql);

        # Search all columns for the Primary Key flag
        foreach ($r as $col) {
            if (($col[$primaryKeyIndex] == $primaryKeyValue)) {
                # Add this column to the primary keys list
                $pk[] = $col[$tableNameIndex];
            }
        }

        # if the return value is preferred as string
        if (!$asArray) {
            $pk = join(',', $pk);
        }

        return $pk;
    }

    /**
     * Get a list of the table fields.
     *
     * @param string $table Table name
     * @param bool $withTableName Add the table name to the column names
     * @param bool $aliased Use an alias as key in the returned array
     * @return array List of the table fields
     */
    public function getColumnNames(string $table, bool $withTableName = false, bool $aliased = false)
    {
        $cols = [];

        if ($this->databaseType === 'mysql') {
            $sql = "SHOW COLUMNS FROM {$table}";
            $tableNameIndex = 'Field';

            # Get all columns from a selected table
            $r = $this->query($sql)->fetchAll();
        } elseif ($this->databaseType === 'sqlite') {
            $sql = "PRAGMA table_info({$table})";
            $tableNameIndex = 'name';

            # Get all columns from a selected table
            $r = $this->query($sql)->fetchAll();
        } else {
            throw new \RuntimeException("Unsupported database type: '{$this->databaseType}'");
        }

        $this->logQuery($sql);

        # Add column names to $cols array
        foreach ($r as $i => $col) {
            $colName = $col[$tableNameIndex];

            if ($aliased) {
                $i = "{$table}_{$colName}";
            }

            if ($withTableName) {
                $colName = "{$table}.${colName}";
            }

            $cols[$i] = $colName;
        }

        return $cols;
    }

    /**
     * Set up a query logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log a query to the configured logger.
     *
     * @param string $sql
     * @param array $params
     */
    public function logQuery(string $sql, array $params = [])
    {
        if ($this->logger instanceof LoggerInterface) {
            $message = $sql . ' => ' . json_encode($params);
            $this->logger->info($message);
        }
    }
}
