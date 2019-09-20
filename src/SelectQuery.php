<?php

namespace ifcanduela\db;

use ifcanduela\db\traits\ConditionBuilder;

class SelectQuery extends Query
{
    /** @var bool */
    protected $distinct = false;

    /** @var string[] */
    protected $columns = ["*"];

    /** @var array */
    protected $joins = [];

    /** @var string[] */
    protected $groupBy;

    /** @var array */
    protected $having;

    /** @var string[] */
    protected $orderBy;

    /** @var int */
    protected $limit;

    /** @var int */
    protected $offset;

    use ConditionBuilder;

    /**
     * Create a SELECT query builder.
     *
     * @param string $columns
     */
    public function __construct(string ...$columns)
    {
        if ($columns) {
            $this->columns(...$columns);
        }
    }

    /**
     * Toggle the DISTINCT flag in a SELECT query.
     *
     * @param bool $enable
     * @return self
     */
    public function distinct(bool $enable = true)
    {
        $this->changed = true;
        $this->distinct = $enable;

        return $this;
    }

    /**
     * Set the columns for the SELECT query.
     *
     * @param string $column
     * @return self
     */
    public function columns(string ...$column)
    {
        $this->changed = true;
        $this->columns = $column;

        return $this;
    }

    /**
     * Append columns for the SELECT query.
     *
     * @param string $column [description]
     * @return self
     */
    public function addColumns(...$column)
    {
        $this->changed = true;
        $this->columns += $column;

        return $this;
    }

    /**
     * Setup a JOIN table.
     *
     * @param string $table
     * @param array $on
     * @return self
     */
    public function join(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ["JOIN", $table, $on];

        return $this;
    }

    /**
     * Setup a INNER JOIN table.
     *
     * @param string $table
     * @param array $on
     * @return self
     */
    public function innerJoin(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ["INNER JOIN", $table, $on];

        return $this;
    }

    /**
     * Setup a LEFT JOIN table.
     *
     * @param string $table
     * @param array $on
     * @return self
     */
    public function leftJoin(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ["LEFT JOIN", $table, $on];

        return $this;
    }

    /**
     * Setup a LEFT OUTER JOIN table.
     *
     * @param string $table
     * @param array $on
     * @return self
     */
    public function leftOuterJoin(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ["LEFT OUTER JOIN", $table, $on];

        return $this;
    }

    /**
     * Setup a RIGHT JOIN table.
     *
     * @param string $table
     * @param array $on
     * @return self
     */
    public function rightJoin(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ["RIGHT JOIN", $table, $on];

        return $this;
    }

    /**
     * Setup a OUTER JOIN table.
     *
     * @param string $table
     * @param array $on
     * @return self
     */
    public function outerJoin(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ["OUTER JOIN", $table, $on];

        return $this;
    }

    /**
     * Setup a FULL OUTER JOIN table.
     *
     * @param string $table
     * @param array $on
     * @return self
     */
    public function fullOuterJoin(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ["FULL OUTER JOIN", $table, $on];

        return $this;
    }

    /**
     * Set grouping fields to the query.
     *
     * @param string $field
     * @return self
     */
    public function groupBy(string ...$field)
    {
        $this->changed = true;
        $this->groupBy = $field;

        return $this;
    }

    /**
     * Set the conditions for groups in HAVING clauses.
     *
     * NOTE: Placeholders in HAVING clauses don't work with SQLite, according to
     * the PHP bug referenced in the @see tag below.
     *
     * @param array $conditions
     * @return self
     * @see https://bugs.php.net/bug.php?id=60281
     */
    public function having(array $conditions)
    {
        $this->changed = true;
        $this->having = $conditions;

        return $this;
    }

    /**
     * Add AND conditions for groups in HAVING clauses.
     *
     * NOTE: Placeholders in HAVING clauses don't work with SQLite, according to
     * the PHP bug referenced in the @see tag below.
     *
     * @param array $conditions
     * @return self
     * @see https://bugs.php.net/bug.php?id=60281
     */
    public function andHaving(array $conditions)
    {
        $this->changed = true;
        $this->having = [
            "AND",
            $this->having,
            $conditions,
        ];

        return $this;
    }

    /**
     * Add OR conditions for groups in HAVING clauses.
     *
     * NOTE: Placeholders in HAVING clauses don't work with SQLite, according to
     * the PHP bug referenced in the @see tag below.
     *
     * @param array $conditions
     * @return self
     * @see https://bugs.php.net/bug.php?id=60281
     */
    public function orHaving(array $conditions)
    {
        $this->changed = true;
        $this->having = [
            "OR",
            $this->having,
            $conditions,
        ];

        return $this;
    }

    /**
     * Setup fields for the ORDER BY clause.
     *
     * @param string $field
     * @return self
     */
    public function orderBy(string ...$field)
    {
        $this->changed = true;
        $this->orderBy = $field;

        return $this;
    }

    /**
     * Set a LIMIT and optional OFFSET.
     *
     * @param int $limit
     * @param int|null $offset
     * @return self
     */
    public function limit(int $limit, int $offset = null)
    {
        $this->changed = true;
        $this->limit = $limit;

        if ($offset !== null) {
            $this->offset = $offset;
        }

        return $this;
    }

    /**
     * Set an OFFSET to the LIMIT clause.
     *
     * @param int $offset
     * @return self
     */
    public function offset(int $offset)
    {
        $this->changed = true;
        $this->offset = $offset;

        return $this;
    }

    /**
     * Build the SELECT query.
     *
     * @return null
     */
    protected function build()
    {
        if (!$this->from) {
            throw new \RuntimeException("No tables provided for FROM clause");
        }

        $this->placeholders = [];
        $this->placeholderCounter = 1;

        $sql = ["SELECT"];

        if ($this->distinct) {
            $sql[] = "DISTINCT";
        }

        $sql[] = implode(", ", $this->columns);

        $sql[] = "FROM";
        $sql[] = implode(", ", $this->from);

        if ($this->joins) {
            foreach ($this->joins as $join) {
                $sql[] = $join[0];
                $sql[] = $join[1];
                $sql[] = "ON";
                $sql[] = is_string($join[2])
                       ? $join[2]
                       : $this->buildConditions($join[2], false);
            }
        }

        if ($this->conditions) {
            $sql[] = "WHERE";
            $sql[] = $this->buildConditions($this->conditions);
        }

        if ($this->groupBy) {
            $sql[] = "GROUP BY";
            $sql[] = implode(", ", $this->groupBy);
        }

        if ($this->having) {
            $sql[] = "HAVING";
            $sql[] = $this->buildConditions($this->having);
        }

        if ($this->orderBy) {
            $sql[] = "ORDER BY";
            $sql[] = implode(", ", $this->orderBy);
        }

        if ($this->limit || $this->offset) {
            $sql[] = "LIMIT";

            $parts = [];

            if ($this->offset) {
                if (!$this->limit) {
                    $this->limit = PHP_INT_MAX;
                }
                $parts[] = $this->addPlaceholder($this->offset);
            }

            if ($this->limit) {
                $parts[] = $this->addPlaceholder($this->limit);
            }

            $sql[] = implode(", ", $parts);
        }

        $this->sql = implode(" ", $sql);
        $this->changed = false;
    }

    /**
     * Get a string representation of the query.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->changed) {
            $this->build();
        }

        return $this->sql;
    }
}
