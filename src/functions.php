<?php

namespace ifcanduela\db;

/**
 * Create an array containing all non-array members of an array, recursively.
 *
 * @param array $array
 * @return array
 */
function array_flatten(array $array): array
{
    $it = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array));
    return iterator_to_array($it, false);
}

/**
 * Create a raw SQL expression.
 *
 * @param mixed $expression
 * @return Expression
 */
function raw($expression): Expression
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
 * @param string|null $endQuote Defaults to double quote (")
 * @return string
 */
function qi(string $identifier, string $startQuote = "\"", string $endQuote = null): string
{
    if ($endQuote === null) {
        $endQuote = $startQuote;
    }

    $words = explode(" ", $identifier);
    $parts = [];

    foreach ($words as $i => $w) {
        if ($w === "*") {
            $parts[] = "*";
        } elseif ($i > 0 && strtoupper($w) == "AS") {
            $parts[] = "AS";
        } else {
            $parts[] = str_replace(".", "{$endQuote}.{$startQuote}", "{$startQuote}{$w}{$endQuote}");
        }
    }

    return implode(" ", $parts);
}

/**
 * Quote an identifier used as a column name.
 *
 * @param string $str
 * @return string
 */
function quote_identifier_column(string $str): string
{
    if (in_array($str[0], ["(", "`", "*"])) {
        return $str;
    }

    $isFunction = false;
    $tableName = "";
    $columnName = $str;
    $alias = "";

    $columnName = trim($columnName);

    if ($space = mb_strpos($str, " ") > -1) {
        [$columnName, $alias] = array_map("trim", explode(" ", $columnName, 2));
        $alias = trim($alias);
        $as = strtoupper(mb_substr($alias, 0, 3));

        if ($as === "AS ") {
            $alias = trim(mb_substr($alias, 3));
        }
    }

    if (preg_match("/(\w+)\((.+)\)/", $columnName, $subpatterns) === 1) {
        $isFunction = $subpatterns[1];
        $columnName = $subpatterns[2];
    }

    if (mb_strpos($columnName, ".") > -1) {
        $columnName = array_map("trim", explode(".", $columnName));
    }

    if (!is_array($columnName)) {
        $columnName = [$columnName];
    }

    $columnName = array_map(function ($columnName) {
        return $columnName === "*" ? $columnName : "`{$columnName}`";
    }, array_filter($columnName));

    $columnName = implode(".", $columnName);
    $alias = $alias && $alias[0] !== "`"? "`{$alias}`" : $alias;

    $result = $isFunction ? "{$isFunction}(${columnName})" : $columnName;

    if ($alias) {
        $result = "$result AS $alias";
    }

    return $result;
}

/**
 * Quote an identifier for use in an ORDER BY clause.
 *
 * @param string $str
 * @return string
 */
function quote_identifier_orderby(string $str): string
{
    $columnName = $str;
    $direction = "";
    $parts = array_map("trim", explode(" ", trim($columnName)));

    if (count($parts) == 2) {
        [$columnName, $direction] = $parts;
    }

    $columnName = quote_identifier_column($columnName);

    if ($direction) {
        $columnName .= strtoupper(" {$direction}");
    }

    return $columnName;
}
