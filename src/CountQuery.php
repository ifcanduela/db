<?php

namespace ifcanduela\db;

class CountQuery extends SelectQuery
{
    protected function build(): void
    {
        array_unshift($this->columns, "COUNT(*)");
        parent::build();
    }
}
