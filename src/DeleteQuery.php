<?php

namespace ifcanduela\db;

use ifcanduela\db\traits\ConditionBuilder;

class DeleteQuery extends Query
{
    use ConditionBuilder;

    public function __construct(string $table = null)
    {
        if ($table) {
            $this->table($table);
        }
    }

    public function build()
    {
        if (!$this->table) {
            throw new \RuntimeException("No tables provided for FROM clause");
        }

        $sql = ['DELETE FROM'];
        $sql[] = $this->table;

        if ($this->conditions) {
            $sql[] = 'WHERE';
            $sql[] = $this->buildConditions($this->conditions);
        }

        $this->sql = implode(' ', $sql);

        $this->changed = false;
    }
}
