<?php

namespace ifcanduela\db;

use ifcanduela\db\traits\ConditionBuilder;

class SelectQuery extends Query
{
    /** @var bool */
    protected $distinct = false;

    /** @var string[] */
    protected $columns = ['*'];

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

    public function __construct(string ...$columns)
    {
        if ($columns) {
            $this->columns(...$columns);
        }
    }

    public function distinct(bool $enable = true)
    {
        $this->changed = true;
        $this->distinct = $enable;

        return $this;
    }

    public function columns(string ...$column)
    {
        $this->changed = true;
        $this->columns = $column;

        return $this;
    }

    public function join(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ['JOIN', $table, $on];

        return $this;
    }

    public function innerJoin(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ['INNER JOIN', $table, $on];

        return $this;
    }

    public function leftJoin(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ['LEFT JOIN', $table, $on];

        return $this;
    }

    public function leftOuterJoin(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ['LEFT OUTER JOIN', $table, $on];

        return $this;
    }

    public function rightJoin(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ['RIGHT JOIN', $table, $on];

        return $this;
    }

    public function outerJoin(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ['OUTER JOIN', $table, $on];

        return $this;
    }

    public function fullOuterJoin(string $table, array $on)
    {
        $this->changed = true;
        $this->joins[] = ['FULL OUTER JOIN', $table, $on];

        return $this;
    }

    public function groupBy(string ...$field)
    {
        $this->changed = true;
        $this->groupBy = $field;

        return $this;
    }

    public function having(array $conditions)
    {
        $this->changed = true;
        $this->having = $conditions;

        return $this;
    }

    public function andHaving(array $conditions)
    {
        $this->changed = true;
        $this->having = [
            'AND',
            $this->having,
            $conditions,
        ];

        return $this;
    }

    public function orHaving(array $conditions)
    {
        $this->changed = true;
        $this->having = [
            'OR',
            $this->having,
            $conditions,
        ];

        return $this;
    }

    public function orderBy(string ...$field)
    {
        $this->changed = true;
        $this->orderBy = $field;

        return $this;
    }

    public function limit(int $limit, int $offset = null)
    {
        $this->changed = true;
        $this->limit = $limit;

        if ($offset !== null) {
            $this->offset = $offset;
        }

        return $this;
    }

    public function offset(int $offset)
    {
        $this->changed = true;
        $this->offset = $offset;

        return $this;
    }

    protected function build()
    {
        if (!$this->from) {
            throw new \RuntimeException("No tables provided for FROM clause");
        }

        $this->placeholders = [];
        $this->placeholderCounter = 1;

        $sql = ['SELECT'];

        if ($this->distinct) {
            $sql[] = 'DISTINCT';
        }

        $sql[] = implode(', ', $this->columns);

        $sql[] = 'FROM';
        $sql[] = implode(', ', $this->from);

        if ($this->joins) {
            foreach ($this->joins as $join) {
                $sql[] = $join[0];
                $sql[] = $join[1];
                $sql[] = 'ON';
                $sql[] = is_string($join[2])
                       ? $join[2]
                       : $this->buildConditions($join[2], false);
            }
        }

        if ($this->conditions) {
            $sql[] = 'WHERE';
            $sql[] = $this->buildConditions($this->conditions);
        }

        if ($this->groupBy) {
            $sql[] = 'GROUP BY';
            $sql[] = implode(', ', $this->groupBy);
        }

        if ($this->having) {
            $sql[] = 'HAVING';
            $sql[] = $this->buildConditions($this->having);
        }

        if ($this->orderBy) {
            $sql[] = 'ORDER BY';
            $sql[] = implode(', ', $this->orderBy);
        }

        if ($this->limit || $this->offset) {
            $sql[] = 'LIMIT';

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

            $sql[] = implode(', ', $parts);
        }

        $this->sql = implode(' ', $sql);
        $this->changed = false;

        return [];
    }

    public function __toString()
    {
        if ($this->changed) {
            $this->build();
        }

        return $this->sql;
    }
}
