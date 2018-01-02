<?php

namespace ifcanduela\db;

class CountQuery extends SelectQuery
{
    function build()
    {
        array_unshift($this->columns, "COUNT(*)");

        return parent::build();
    }
}
