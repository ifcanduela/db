<?php

namespace ifcanduela\db;

use ifcanduela\db\traits\ConditionBuilder;
use RuntimeException;

class UpdateQuery extends Query
{
    protected string|array $table;

    protected array $set = [];

    use ConditionBuilder;

    /**
     * UpdateQuery constructor.
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
     * Columns and values to set.
     *
     * @param array $values
     * @return self
     */
    public function set(array $values): UpdateQuery
    {
        $this->changed = true;
        $this->set = $values;

        return $this;
    }

    /**
     * Build the query,
     *
     * @return void
     */
    protected function build(): void
    {
        if (!isset($this->table)) {
            throw new RuntimeException("No tables provided for UPDATE clause");
        }

        if (!count($this->set)) {
            throw new RuntimeException("No columns provided for UPDATE clause");
        }

        $sql = ["UPDATE"];
        $sql[] = $this->table;

        $sql[] = "SET";
        $set = [];

        foreach ($this->set as $field => $value) {
            $placeholder = $this->addPlaceholder($value);
            $set[] = "$field = $placeholder";
        }

        $sql[] = implode(", ", $set);

        if ($this->conditions) {
            $sql[] = "WHERE";
            $sql[] = $this->buildConditions($this->conditions);
        }

        $this->sql = implode(" ", $sql);

        $this->changed = false;
    }
}
