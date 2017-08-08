<?php

namespace ifcanduela\db;

/**
 * Create an array containing all non-array members of an array, recursively.
 *
 * @param array $array
 * @return array
 */
function array_flatten(array $array)
{
    $it = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array));
    return iterator_to_array($it, false);
}

/**
 * [raw description]
 * @param mixed $expression
 * @return \ifcanduela\db\Expression
 */
function raw($expression)
{
    return new Expression($expression);
}

/**
 * Quote an SQL identifier.
 *
 * If `$endQuote` is not supplied, `$startQuote` will be used as end quote.
 *
 * @param string $identifier
 * @param string $startQuote Defaults to double quote (")
 * @param string $endQuote Defaults to double quote (")
 * @return string
 */
function qi($identifier, $startQuote = '"', $endQuote = null)
{
    if ($endQuote === null) {
        $endQuote = $startQuote;
    }

    $words = explode(' ', $identifier);
    $parts = [];

    foreach ($words as $i => $w) {
        if ($w === '*') {
            $parts[] = '*';
        } elseif ($i > 0 && strtoupper($w) == 'AS') {
            $parts[] = 'AS';
        } else {
            $parts[] = str_replace('.', "{$endQuote}.{$startQuote}", "{$startQuote}{$w}{$endQuote}");
        }
    }

    return implode(' ', $parts);
}
