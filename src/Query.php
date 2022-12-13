<?php

namespace ifcanduela\db;

abstract class Query
{
    /** @var string|string[] */
    protected string|array $table;

    /** @var string[] */
    protected array $from;

    /** @var array */
    protected array $conditions = [];

    /** @var bool */
    protected bool $changed = true;

    /** @var string */
    protected string $sql;

    /** @var array */
    protected array $placeholders = [];

    /**
     * Create a SELECT query.
     *
     * @param string ...$field
     * @return SelectQuery
     */
    public static function select(string ...$field): SelectQuery
    {
        return new SelectQuery(...$field);
    }

    /**
     * Create a SELECT COUNT(*) query.
     *
     * @return CountQuery
     */
    public static function count(): CountQuery
    {
        return new CountQuery();
    }

    /**
     * Create an INSERT query.
     *
     * @param string|null $table Name of the table to insert into
     * @return InsertQuery
     */
    public static function insert(string $table = null): InsertQuery
    {
        return new InsertQuery($table);
    }

    /**
     * Create an UPDATE query.
     *
     * @param string|null $table Name of the table to update
     * @return UpdateQuery
     */
    public static function update(string $table = null): UpdateQuery
    {
        return new UpdateQuery($table);
    }

    /**
     * Create a DELETE query.
     *
     * @param string|null $table Name of the table to delete from
     * @return DeleteQuery
     */
    public static function delete(string $table = null): DeleteQuery
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
     * @return self
     */
    public function table(string $table): Query
    {
        $this->changed = true;
        $this->table = $table;

        return $this;
    }

    /**
     * Set the list of tables on which a SELECT query operates.
     *
     * @param  string ...$table
     * @return self
     */
    public function from(string ...$table): Query
    {
        $this->changed = true;
        $this->from = $table;

        return $this;
    }

    /**
     * Set the conditions for SELECT, UPDATE and DELETE queries.
     *
     * @param  array  $conditions
     * @return self
     */
    public function where(array $conditions): Query
    {
        $this->changed = true;
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * Add conditions for SELECT, UPDATE and DELETE queries with an AND operator.
     *
     * @param  array  $conditions
     * @return self
     */
    public function andWhere(array $conditions): Query
    {
        $this->changed = true;

        if ($this->conditions) {
            $this->conditions = [
                "AND",
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
     * @return self
     */
    public function orWhere(array $conditions): Query
    {
        $this->changed = true;

        if ($this->conditions) {
            $this->conditions = [
                "OR",
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
    public function getSql(): string
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
    public function getParams(): array
    {
        if ($this->changed) {
            $this->build();
        }

        return $this->placeholders;
    }

    /**
     * Build the query,
     *
     * @return void
     */
    abstract protected function build(): void;
}
