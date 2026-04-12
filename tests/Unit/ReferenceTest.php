<?php /** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit;

use Exception;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SaschaKliche\PhpXlsxReader\Utils\Reference;

class ReferenceTest extends TestCase
{
    #[Test]
    function it_calculates_index_only_if_needed()
    {
        Reference::convertIndexToCellAddress(1, 15);
        Reference::convertIndexToCellAddress(1, 500);
        self::assertEquals(0, Reference::getNumberOfCalculatedIndexes());

        // pre-built up to ZZ (702)
        Reference::convertIndexToCellAddress(1, 1000);
        self::assertEquals(1, Reference::getNumberOfCalculatedIndexes());

        Reference::convertIndexToCellAddress(1, 1000);
        self::assertEquals(1, Reference::getNumberOfCalculatedIndexes());
    }

    static function provideIndexData(): Generator
    {
        yield 'A1' => ['A4', [Reference::COLUMN => 1, Reference::ROW => 4]];
        yield 'Z1' => ['$Z1', [Reference::COLUMN => 26, Reference::ROW => 1]];
        yield 'ZZ1' => ['ZZ$1', [Reference::COLUMN => 702, Reference::ROW => 1]];
        yield 'AZZ42' => ['$AZZ$42', [Reference::COLUMN => 1378, Reference::ROW => 42]];
    }

    #[Test]
    #[DataProvider('provideIndexData')]
    function it_converts_cell_address_to_indexes(string $cellAddress, array $expectedResult)
    {
        $result = Reference::convertCellAddressToIndex($cellAddress);

        self::assertEquals($expectedResult, $result);
    }

    static function provideColumnAddressData(): Generator
    {
        yield 'A' => [1, 'A'];
        yield 'B' => [2, 'B'];
        yield 'C' => [3, 'C'];
        yield 'X' => [24, 'X'];
        yield 'Y' => [25, 'Y'];
        yield 'Z' => [26, 'Z'];
        yield 'AA' => [27, 'AA'];
        yield 'AB' => [28, 'AB'];
        yield 'AC' => [29, 'AC'];
        yield 'AD' => [30, 'AD'];
        yield 'AZ' => [52, 'AZ'];
        yield 'BA' => [53, 'BA'];
        yield 'BB' => [54, 'BB'];
        yield 'BC' => [55, 'BC'];
        yield 'BY' => [77, 'BY'];
        yield 'BZ' => [78, 'BZ'];
        yield 'CA' => [79, 'CA'];
        yield 'CZ' => [104, 'CZ'];
        yield 'DA' => [105, 'DA'];
        yield 'DZ' => [130, 'DZ'];
        yield 'EA' => [131, 'EA'];
        yield 'EZ' => [156, 'EZ'];
        yield 'FA' => [157, 'FA'];
        yield 'ZA' => [677, 'ZA'];
        yield 'ZB' => [678, 'ZB'];
        yield 'ZY' => [701, 'ZY'];
        yield 'ZZ' => [702, 'ZZ'];
        yield 'AAA' => [703, 'AAA'];
        yield 'AAB' => [704, 'AAB'];
        yield 'AAC' => [705, 'AAC'];
        yield 'AZZ' => [1378, 'AZZ'];
        yield 'BAA' => [1379, 'BAA'];
        yield 'BAF' => [1384, 'BAF'];
    }

    #[Test]
    #[DataProvider('provideColumnAddressData')]
    function it_converts_column_indexes_to_addresses($columnIndex, $expectedCellAddress)
    {
        $columnAddress = Reference::convertColumnIndexToAddress($columnIndex);

        self::assertEquals($expectedCellAddress, $columnAddress);
    }

    static function provideCellAddressData(): Generator
    {
        yield 'A1' => [1, 1, 'A1'];
        yield 'B1' => [1, 2, 'B1'];
        yield 'C4' => [4, 3, 'C4'];
        yield 'X1' => [1, 24, 'X1'];
        yield 'Y1' => [1, 25, 'Y1'];
        yield 'Z1' => [1, 26, 'Z1'];
        yield 'AA1' => [1, 27, 'AA1'];
        yield 'AB1' => [1, 28, 'AB1'];
        yield 'AC1' => [1, 29, 'AC1'];
        yield 'AD1' => [1, 30, 'AD1'];
        yield 'AZ1' => [1, 52, 'AZ1'];
        yield 'BA1' => [1, 53, 'BA1'];
        yield 'BB1' => [1, 54, 'BB1'];
        yield 'BC1' => [1, 55, 'BC1'];
        yield 'BY1' => [1, 77, 'BY1'];
        yield 'BZ1' => [1, 78, 'BZ1'];
        yield 'CA1' => [1, 79, 'CA1'];
        yield 'CZ1' => [1, 104, 'CZ1'];
        yield 'DA1' => [1, 105, 'DA1'];
        yield 'DZ1' => [1, 130, 'DZ1'];
        yield 'EA1' => [1, 131, 'EA1'];
        yield 'EZ1' => [1, 156, 'EZ1'];
        yield 'FA1' => [1, 157, 'FA1'];
        yield 'ZA1' => [1, 677, 'ZA1'];
        yield 'ZB1' => [1, 678, 'ZB1'];
        yield 'ZY1' => [1, 701, 'ZY1'];
        yield 'ZZ1' => [1, 702, 'ZZ1'];
        yield 'AAA1' => [1, 703, 'AAA1'];
        yield 'AAB1' => [1, 704, 'AAB1'];
        yield 'AAC1' => [1, 705, 'AAC1'];
        yield 'AZZ1' => [1, 1378, 'AZZ1'];
        yield 'BAA1' => [1, 1379, 'BAA1'];
        yield 'BAF1' => [1, 1384, 'BAF1'];
    }

    #[Test]
    #[DataProvider('provideCellAddressData')]
    function it_converts_indexes_to_cell_address($rowIndex, $columnIndex, $expectedCellAddress)
    {
        $cellAddress = Reference::convertIndexToCellAddress($rowIndex, $columnIndex);

        self::assertEquals($expectedCellAddress, $cellAddress);
    }

    #[Test]
    function illegal_index_values_throw_an_exception()
    {
        $this->expectException(Exception::class);

        Reference::convertIndexToCellAddress(0, 42);
    }
}
