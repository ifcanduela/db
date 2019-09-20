<?php

namespace ifcanduela\db\traits;

use function ifcanduela\db\array_flatten;
use ifcanduela\db\Expression;

trait ConditionBuilder
{
    /** @var int */
    protected $placeholderCounter = 1;

    /** @var bool  */
    protected $usePlaceholders = true;

    public function buildConditions(array $conditions, $usePlaceholders = true)
    {
        $this->usePlaceholders = $usePlaceholders;
        $c = [];
        $joiner = "AND";

        $i = 0;

        foreach ($conditions as $key => $condition) {
            if ($i === 0 && $key === 0) {
                // it's an AND/OR
                $joiner = strtoupper($condition);
                continue;
            } elseif (is_numeric($key)) {
                $c[] = $this->buildConditions($condition, $usePlaceholders);
            } else {
                $c[] = $this->buildCondition($key, $condition, $usePlaceholders);
            }

            $i++;
        }

        return "(" . implode(" {$joiner} ", $c) . ")";
    }

    public function buildCondition($key, $value, $usePlaceholders = true)
    {
        $this->usePlaceholders = $usePlaceholders;
        $clause = null;

        if (is_array($value)) {
            $operator = strtoupper(array_shift($value));

            switch ($operator) {
                case "IN":
                case "NOT IN":
                    $placeholders = [];
                    $values = array_flatten($value);

                    foreach ($values as $v) {
                        $placeholders[] = $this->addPlaceholder($v);
                    }

                    $s = implode(", ", $placeholders);

                    $clause = "{$key} {$operator} ({$s})";
                    break;
                case "IS":
                case "NOT IS":
                case "IS NOT":
                    $clause = "{$key} {$operator} NULL";
                    break;
                case "BETWEEN":
                case "NOT BETWEEN":
                    $from_placeholder = $this->addPlaceholder($value[0]);
                    $to_placeholder = $this->addPlaceholder($value[1]);

                    $clause = "{$key} {$operator} {$from_placeholder} AND {$to_placeholder}";
                    break;
                case "LIKE":
                case "NOT LIKE":
                    $placeholder = $this->addPlaceholder($value[0]);
                    $clause = "{$key} {$operator} {$placeholder}";
                    break;
                default:
                    $placeholder = $this->addPlaceholder($value[0]);

                    $clause = "{$key} {$operator} {$placeholder}";
            }

            return $clause;
        }

        if (is_null($value)) {
            return "{$key} IS NULL";
        }

        $placeholder = $this->addPlaceholder($value);

        return "{$key} = {$placeholder}";
    }

    private function addPlaceholder($value)
    {
        if (!$this->usePlaceholders) {
            return $value;
        }

        if ($value instanceof Expression) {
            return (string) $value;
        }

        $placeholderName = ":p_" . $this->placeholderCounter;
        $this->placeholderCounter++;
        $this->placeholders[$placeholderName] = $value;

        return $placeholderName;
    }
}
