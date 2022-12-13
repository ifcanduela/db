<?php

use function ifcanduela\db\quote_identifier_column;

class FunctionsTest extends PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider columnNamesProvider()
     */
    public function testQuoteColumnNames($columnName, $expected)
    {
        $this->assertEquals($expected, quote_identifier_column($columnName));
    }

    public function columnNamesProvider(): array
    {
        return [
            ["name", "`name`"],
            ["project.created", "`project`.`created`"],
            ["updated AS updateDate", "`updated` AS `updateDate`"],
            ["users.id AS userId", "`users`.`id` AS `userId`"],
            ["password `pass`", "`password` AS `pass`"],
        ];
    }
}
