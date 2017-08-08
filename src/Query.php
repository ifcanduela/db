<?php

namespace ifcanduela\db;

abstract class Query
{
    /** @var string[] */
    protected $table;

    /** @var string[] */
    protected $from;

    /** @var array */
    protected $conditions;

    /** @var bool */
    protected $changed = true;

    /** @var string */
    protected $sql;

    /** @var array */
    protected $placeholders = [];

    /**
     * Create a SELECT query.
     *
     * @param  string $fields List of fields to select
     * @return SelectQuery
     */
    public static function select(string ...$fields)
    {
        return new SelectQuery(...$fields);
    }

    /**
     * Create an INSERT query.
     *
     * @param  string $table Name of the table to insert into
     * @return InsertQuery
     */
    public static function insert(string $table = null)
    {
        return new InsertQuery($table);
    }

    /**
     * Create an UPDATE query.
     *
     * @param  string $table Name of the table to update
     * @return UpdateQuery
     */
    public static function update(string $table = null)
    {
        return new UpdateQuery($table);
    }

    /**
     * Create a DELETE query.
     *
     * @param  string $table Name of the table to delete from
     * @return DeleteQuery
     */
    public static function delete(string $table = null)
    {
        return new DeleteQuery($table);
    }

    /**
     * Set the main table on which the query operates.
     *
     * This is the table that follows UPDATE TABLE, INSERT INTO and
     * DELETE FROM.
     *
     * @param  string $table
     * @return static
     */
    public function table(string $table)
    {
        $this->changed = true;
        $this->table = $table;

        return $this;
    }

    /**
     * Set the list of tables on which a SELECT query operates.
     *
     * @param  string $table
     * @return static
     */
    public function from(string ...$table)
    {
        $this->changed = true;
        $this->from = $table;

        return $this;
    }

    /**
     * Set the conditions for SELECT, UPDATE and DELETE queries.
     *
     * @param  array  $conditions
     * @return static
     */
    public function where(array $conditions)
    {
        $this->changed = true;
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * Add conditions for SELECT, UPDATE and DELETE queries with an AND operator.
     *
     * @param  array  $conditions
     * @return static
     */
    public function andWhere(array $conditions)
    {
        $this->changed = true;

        if ($this->conditions) {
            $this->conditions = [
                'AND',
                $this->conditions,
                $conditions,
            ];
        } else {
            $this->conditions = $conditions;
        }

        return $this;
    }

    /**
     * Add conditions for SELECT, UPDATE and DELETE queries with an ON operator.
     *
     * @param  array  $conditions
     * @return static
     */
    public function orWhere(array $conditions)
    {
        $this->changed = true;

        if ($this->conditions) {
            $this->conditions = [
                'OR',
                $this->conditions,
                $conditions,
            ];
        } else {
            $this->conditions = $conditions;
        }

        return $this;
    }

    /**
     * Get the SQL query for a prepared statement.
     *
     * @return string
     */
    public function getSql()
    {
        if ($this->changed) {
            $this->build();
        }

        return $this->sql;
    }

    /**
     * Get the parameters for a prepared statement query.
     *
     * @return array
     */
    public function getParams()
    {
        if ($this->changed) {
            $this->build();
        }

        return $this->placeholders;
    }

    abstract protected function build();
}
