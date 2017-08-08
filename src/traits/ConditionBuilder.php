<?php

namespace ifcanduela\db\traits;

use function ifcanduela\db\array_flatten;

trait ConditionBuilder
{
    /** @var int */
    protected $placeholderCounter = 1;
    protected $usePlaceholders = true;

    public function buildConditions(array $conditions, $usePlaceholders = true)
    {
        $this->usePlaceholders = $usePlaceholders;
        $c = [];
        $joiner = 'AND';

        foreach ($conditions as $i => $condition) {
            if ($i === 0) {
                // it's an AND/OR
                $joiner = strtoupper($condition);
                continue;
            } elseif (is_numeric($i)) {
                $c[] = $this->buildConditions($condition, $usePlaceholders);
            } else {
                $c[] = $this->buildCondition($i, $condition, $usePlaceholders);
            }
        }

        return '(' . implode(" {$joiner} ", $c) . ')';
    }

    public function buildCondition($key, $value, $usePlaceholders = true)
    {
        $this->usePlaceholders = $usePlaceholders;
        $clause = null;

        if (is_array($value)) {
            $operator = strtoupper(array_shift($value));

            switch ($operator) {
                case 'IN':
                case 'NOT IN':
                    $placeholders = [];
                    $values = array_flatten($value);

                    foreach ($values as $v) {
                        $placeholders[] = $this->addPlaceholder($v);
                    }

                    $s = implode(', ', $placeholders);

                    $clause = "{$key} {$operator} ({$s})";
                    break;
                case 'IS':
                case 'NOT IS':
                case 'IS NOT':
                    $clause = "{$key} {$operator} NULL";
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $from_placeholder = $this->addPlaceholder($value[0]);
                    $to_placeholder = $this->addPlaceholder($value[1]);

                    $clause = "{$key} {$operator} {$from_placeholder} AND {$to_placeholder}";
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                    $placeholder = $this->addPlaceholder($value[0]);
                    $clause = "{$key} {$operator} {$placeholder}";
                    break;
                default:
                    $placeholder = $this->addPlaceholder($value[0]);

                    $clause = "{$key} {$operator} {$placeholder}";
            }

            return $clause;
        } else {
            $placeholder = $this->addPlaceholder($value);

            return is_null($value) ? "{$key} IS NULL" : "{$key} = {$placeholder}";
        }

        throw new \RuntimeException("Invalid condition for {$key}");
    }

    private function addPlaceholder($value)
    {
        if (!$this->usePlaceholders) {
            return $value;
        }

        if (is_a($value, \ifcanduela\db\Expression::class)) {
            return (string) $value;
        }

        $placeholderName = ':p_' . $this->placeholderCounter;
        $this->placeholderCounter++;
        $this->placeholders[$placeholderName] = $value;

        return $placeholderName;
    }

}
