<?php

namespace ifcanduela\db;

use ifcanduela\db\traits\ConditionBuilder;

class UpdateQuery extends Query
{
    protected $table;
    protected $set;

    use ConditionBuilder;

    public function __construct(string $table = null)
    {
        if ($table) {
            $this->table($table);
        }
    }

    public function set(array $values)
    {
        $this->changed = true;
        $this->set = $values;

        return $this;
    }

    protected function build()
    {
        if (!$this->table) {
            throw new \RuntimeException("No tables provided for UPDATE clause");
        }

        if (!$this->set) {
            throw new \RuntimeException("No columns provided for UPDATE clause");
        }

        $sql = ['UPDATE'];
        $sql[] = $this->table;

        $sql[] = 'SET';
        $set = [];

        foreach ($this->set as $field => $value) {
            $placeholder = $this->addPlaceholder($value);
            $set[] = "{$field} = {$placeholder}";
        }

        $sql[] = implode(', ', $set);

        if ($this->conditions) {
            $sql[] = 'WHERE';
            $sql[] = $this->buildConditions($this->conditions);
        }

        $this->sql = implode(' ', $sql);

        $this->changed = false;
    }
}
