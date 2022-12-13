<?php

namespace ifcanduela\db;

use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;
use RuntimeException;

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
    const DB_MYSQL = "mysql";

    const DB_SQLITE = "sqlite";

    private string $databaseType;

    private bool $isWritable = true;

    private ?LoggerInterface $logger = null;

    private array $queryHistory = [];

    /** @var array Default PDO options set by the factory methods */
    private static array $defaultOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * Create a database connection to SQLite ot MySQL, based on a configuration array.
     *
     * Array keys are as follows:
     *
     * engine  | required |             | Either 'mysql' or 'sqlite'
     * file    | required | SQLite only | Path and filename or ':memory:'
     * host    | required | MySQL only  | MySQL host name or IP address
     * name    | required | MySQL only  | Schema name
     * user    | optional | MySQL only  | Username
     * pass    | optional | MySQL only  | Password
     * options | optional |             | Additional PDO connection options
     *
     * @param array $config
     * @return Database
     */
    public static function fromArray(array $config): Database
    {
        if (!isset($config["engine"])) {
            throw new InvalidArgumentException("Missing engine: must be either 'mysql' or 'sqlite'");
        }

        if (!in_array(strtolower($config["engine"]), [self::DB_MYSQL, self::DB_SQLITE])) {
            throw new InvalidArgumentException("Unsupported engine `{$config['engine']}`");
        }

        if ($config["engine"] === self::DB_SQLITE) {
            return static::sqlite(
                $config["file"],
                $config["options"] ?? []
            );
        }

        if ($config["engine"] === self::DB_MYSQL) {
            return static::mysql(
                $config["host"],
                $config["name"],
                $config["user"] ?? null,
                $config["pass"] ?? null,
                $config["options"] ?? []
            );
        }

        throw new InvalidArgumentException("Invalid database configuration: `" . json_encode($config) . "`");
    }

    /**
     * Factory for a Sqlite connection.
     *
     * @param string $file
     * @param array $options
     * @return static
     */
    public static function sqlite(string $file, array $options = []): Database
    {
        $options = array_replace(static::$defaultOptions, $options);
        $instance = new static("sqlite:$file", null, null, $options);
        $instance->databaseType = self::DB_SQLITE;
        $instance->isWritable = ($file === ":memory:") || (is_writable(dirname($file)) && is_writable($file));

        return $instance;
    }

    /**
     * Factory for a MySQL connection.
     *
     * @param string $host
     * @param string $dbname
     * @param string|null $user
     * @param string|null $password
     * @param array $options
     * @return static
     */
    public static function mysql(string $host, string $dbname, string $user = null, string $password = null, array $options = []): Database
    {
        $options = array_replace(static::$defaultOptions, $options);
        $instance = new static("mysql:host=$host;dbname=$dbname", $user, $password, $options);
        $instance->databaseType = self::DB_MYSQL;

        return $instance;
    }

    /**
     * Check if the database is writable.
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->isWritable;
    }

    /**
     * Get a list of tables in the database.
     *
     * @return string[]
     */
    public function getTableNames(): array
    {
        if ($this->databaseType === self::DB_MYSQL) {
            $sql = "SHOW TABLES";
        } elseif ($this->databaseType === self::DB_SQLITE) {
            $sql = "SELECT name FROM sqlite_master WHERE type='table'";
        } else {
            throw new RuntimeException("Unsupported database type: `$this->databaseType`");
        }

        // Get a list of the tables
        $r = $this->query($sql)->fetchAll();

        return array_map(function ($t) {
            return reset($t);
        }, $r);
    }

    /**
     * Get a list of the table fields.
     *
     * @param string $table Table name
     * @param bool $withTableName Add the table name to the column names
     * @param bool $aliased Use an alias as key in the returned array
     * @return array List of the table fields
     */
    public function getColumnNames(string $table, bool $withTableName = false, bool $aliased = false): array
    {
        $cols = [];

        if ($this->databaseType === self::DB_MYSQL) {
            $sql = "SHOW COLUMNS FROM $table";
            $tableNameIndex = "Field";
        } elseif ($this->databaseType === self::DB_SQLITE) {
            $sql = "PRAGMA table_info($table)";
            $tableNameIndex = "name";
        } else {
            throw new RuntimeException("Unsupported database type: `$this->databaseType`");
        }

        // Get all columns from a selected table
        $r = $this->query($sql)->fetchAll();

        // Add column names to $cols array
        foreach ($r as $i => $col) {
            $colName = $col[$tableNameIndex];

            if ($aliased) {
                $i = implode("_", [$table, $colName]);
            }

            if ($withTableName) {
                $colName = implode(".", [$table, $colName]);
            }

            $cols[$i] = $colName;
        }

        return $cols;
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
     * @param bool $asArray Return multiple keys as an array (default is false)
     * @return string|array A comma-separated string with the primary key fields or an
     *                      array if $asArray is true
     */
    public function getPrimaryKeys(string $table, bool $asArray = false): array|string
    {
        $pk = [];

        if ($this->databaseType === self::DB_MYSQL) {
            $sql = "SHOW COLUMNS FROM $table";
            $primaryKeyIndex = "Key";
            $primaryKeyValue = "PRI";
            $tableNameIndex = "Field";
        } elseif ($this->databaseType === self::DB_SQLITE) {
            $sql = "PRAGMA table_info($table)";
            $primaryKeyIndex = "pk";
            $primaryKeyValue = 1;
            $tableNameIndex = "name";
        } else {
            throw new RuntimeException("Unsupported database type: `$this->databaseType`");
        }

        $r = $this->query($sql)->fetchAll();

        // Search all columns for the Primary Key flag
        foreach ($r as $col) {
            if ($col[$primaryKeyIndex] == $primaryKeyValue) {
                // Add this column to the primary keys list
                $pk[] = $col[$tableNameIndex];
            }
        }

        // If the return value is preferred as array
        if ($asArray) {
            return $pk;
        }

        return join(",", $pk);
    }

    /**
     * Find out if the database contains a table.
     *
     * @param string $tableName Table name
     * @return bool True if the table exists, false otherwise
     */
    public function tableExists(string $tableName): bool
    {
        try {
            $sql = "SELECT 1 FROM $tableName LIMIT 1";
            $this->prepare($sql);
            $this->logQuery("[PREPARE ONLY] $sql");
        } catch (PDOException) {
            return false;
        }

        return true;
    }

    /**
     * Find out if a table in the database contains a column.
     *
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @return bool True if the column exists, false otherwise
     */
    public function columnExists(string $tableName, string $columnName): bool
    {
        if ($this->tableExists($tableName)) {
            $names = array_flip($this->getColumnNames($tableName));

            return array_key_exists($columnName, $names);
        }

        return false;
    }

    /**
     * Execute an SQL query and return the number of affected rows.
     *
     * @param string $statement
     * @return false|int
     * @see https://www.php.net/manual/en/pdo.exec.php
     */
    #[\ReturnTypeWillChange]
    public function exec(string $statement): false|int
    {
        $result = parent::exec($statement);
        $this->logQuery($statement, [], true, $result);

        return $result;
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object.
     *
     * @param string $query
     * @param int|null $fetchMode
     * @param mixed ...$fetchModeArgs
     * @return PDOStatement|false
     * @see https://www.php.net/manual/en/pdo.query.php
     */
    #[\ReturnTypeWillChange]
    public function query(string $query, ?int $fetchMode = null, ...$fetchModeArgs): PDOStatement|false
    {
        $result = parent::query($query, $fetchMode, ...$fetchModeArgs);
        $this->logQuery($query, [], true, $result->rowCount());

        return $result;
    }

    /**
     * Run a query using a prepared statement and return an appropriate value.
     *
     * The return value is an array of rows for SELECT statements and a number of affected rows
     * for any other type of statement.
     *
     * @param Query|string $sql
     * @param array $params
     * @param bool $returnStatement
     * @param int $fetchMode
     * @return array|int|PDOStatement
     */
    public function run(Query|string $sql, array $params = [], bool $returnStatement = false, int $fetchMode = PDO::FETCH_ASSOC): int|array|PDOStatement
    {
        if ($sql instanceof Query) {
            $params = $sql->getParams();
            $sql = $sql->getSql();
        }

        $stm = $this->prepare($sql);
        $success = $stm->execute($params);

        $this->logQuery($sql, $params, $success, $stm->rowCount());

        if ($returnStatement) {
            return $stm;
        } elseif ($this->isSelectQuery($sql)) {
            return $stm->fetchAll($fetchMode);
        } else {
            return $stm->rowCount();
        }
    }

    /**
     * Run a query using a prepared statement and return the first row.
     *
     * @param Query|string $sql
     * @param array $params
     * @param int $rowNumber
     * @return array|null
     */
    public function row(Query|string $sql, array $params = [], int $rowNumber = 0): ?array
    {
        $result = $this->run($sql, $params);

        return $result[$rowNumber] ?? null;
    }

    /**
     * Run a query using a prepared statement and return the first column of the first row.
     *
     * @param Query|string $sql
     * @param array $params
     * @param int|string $columnName
     * @return string|null
     */
    public function cell(Query|string $sql, array $params = [], int|string $columnName = 0): ?string
    {
        $result = $this->run($sql, $params, false, PDO::FETCH_BOTH);

        return $result[0][$columnName] ?? null;
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
     * @param bool $success
     * @param int|null $affectedRows
     */
    public function logQuery(string $sql, array $params = [], bool $success = true, int $affectedRows = null)
    {
        $this->queryHistory[] = [microtime(true), $sql, $params, $success, $affectedRows];

        if (isset($this->logger)) {
            $message = $sql . " => " . json_encode($params);

            if (!$success) {
                $message = "[ERROR] $message";
            }

            $this->logger->info($message);
        }
    }

    /**
     * Check if a SQL string represents a SELECT query.
     *
     * @param string $sql
     * @return bool
     */
    protected function isSelectQuery(string $sql): bool
    {
        $start = substr(trim($sql), 0, 6);
        $command = strtoupper($start);

        return $command === "SELECT";
    }

    /**
     * Get all queries run by this database connection.
     *
     * Returns an array with queries as arrays of <timestamp: float, sql: string, params: array>
     *
     * @return array
     */
    public function getQueryHistory(): array
    {
        return $this->queryHistory;
    }
}
