<?php

namespace ifcanduela\db;

use ifcanduela\db\traits\ConditionBuilder;

class InsertQuery extends Query
{
    /** @var array[] */
    protected $values;

    use ConditionBuilder;

    /**
     * InsertQuery constructor.
     *
     * @param string|NULL $table
     */
    public function __construct(string $table = null)
    {
        if ($table) {
            $this->into($table);
        }
    }

    /**
     * Specify the table to insert into.
     *
     * @param string $table
     * @return self
     */
    public function into(string $table)
    {
        $this->table($table);

        return $this;
    }

    /**
     * @param array[] $values,... Rows to insert
     * @return self
     */
    public function values(array ...$values)
    {
        $this->values = $values;

        return $this;
    }

    /**
     * Build the query,
     */
    public function build()
    {
        if (!$this->table) {
            throw new \RuntimeException("No tables provided for INSERT clause");
        }

        if (!$this->values) {
            throw new \RuntimeException("No values provided for INSERT clause");
        }

        $sql = ['INSERT INTO'];
        $sql[] = $this->table;

        $columns = array_keys($this->values[0]);
        $sql[] = '(' . implode(', ', $columns) . ')';

        $sql[] = 'VALUES';
        $values = [];

        foreach ($this->values as $row) {
            $row_values = [];
            foreach ($row as $value) {
                $row_values[] = $this->addPlaceholder($value);
            }

            $values[] = '(' . implode(', ', $row_values) . ')';
        }

        $sql[] = implode(', ', $values);

        $this->sql = implode(' ', $sql);
        $this->changed = false;
    }
}
