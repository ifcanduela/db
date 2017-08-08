<?php

namespace ifcanduela\db;

use ifcanduela\db\traits\ConditionBuilder;

class InsertQuery extends Query
{
    protected $values;

    use ConditionBuilder;

    public function __construct(string $table = null)
    {
        if ($table) {
            $this->into($table);
        }
    }

    public function into(string $table)
    {
        $this->table($table);

        return $this;
    }

    public function values(array ...$values)
    {
        $this->values = $values;

        return $this;
    }

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
