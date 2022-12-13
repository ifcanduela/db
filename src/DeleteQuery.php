<?php

namespace ifcanduela\db;

use ifcanduela\db\traits\ConditionBuilder;
use RuntimeException;

class DeleteQuery extends Query
{
    use ConditionBuilder;

    /**
     * DeleteQuery constructor.
     *
     * @param string|null $table
     */
    public function __construct(string $table = null)
    {
        if ($table) {
            $this->table($table);
        }
    }

    /**
     * Build the query,
     *
     * @return void
     */
    public function build(): void
    {
        if (!isset($this->table)) {
            throw new RuntimeException("No tables provided for FROM clause");
        }

        $sql = ["DELETE FROM"];
        $sql[] = $this->table;

        if ($this->conditions) {
            $sql[] = "WHERE";
            $sql[] = $this->buildConditions($this->conditions);
        }

        $this->sql = implode(" ", $sql);

        $this->changed = false;
    }
}
