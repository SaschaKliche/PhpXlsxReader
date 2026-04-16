<?php /** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit;

use DateTime;
use PHPUnit\Framework\Attributes\Test;
use SaschaKliche\PhpXlsxReader\Model\Cell;
use SaschaKliche\PhpXlsxReader\Tests\AbstractTestCase;
use SaschaKliche\PhpXlsxReader\XlsxReader;

class CellFormatTest extends AbstractTestCase
{
    #[Test]
    function it_reads_format_strings()
    {
        $expected = [
            'Date' => [
                'B1' => 'dd/mm/yy\ hh:mm:ss',
                'B2' => 'd',
                'B9' => 'mmmm',
                'B23' => 'mm-dd-yy',
                'B35' => '[$-409]d/m/yy\ h:mm\ AM/PM;@',
                'B56' => '[$-407]dddd\,\ d/\ mmmm\ yyyy',
            ],
            'Numeric' => [
                'B2' => '0.000',
                'B3' => '#,##0',
                'B4' => '0.00%',
                'B5' => '# ?/?',
                'B16' => '#,##0.00;[Red](#,##0.00)',
                'B22' => '_-* #,##0.00\ "€"_-;\-* #,##0.00\ "€"_-;_-* "-"??\ "€"_-;_-@_-',
            ],
            'Currency' => [
                'B1' => '_-* #,##0.00\ "€"_-;\-* #,##0.00\ "€"_-;_-* "-"??\ "€"_-;_-@_-',
                'B2' => '#,##0.00\ "€"',
            ],
            'Elapsed' => [
                'B1' => '0.000',
                'C1' => '[h]:mm:ss',
            ],
        ];

        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'FormatStrings.xlsx');

        foreach ($reader->returnCellObjects()->read() as $worksheetName => $rows) {
            self::assertIsString($worksheetName);

            foreach ($rows as $rowIndex => $row) {
                self::assertIsInt($rowIndex);

                foreach ($row as $cellAddress => $cell) {
                    self::assertIsString($cellAddress);
                    self::assertInstanceOf(Cell::class, $cell);

                    if ($cell->getCellFormatString() === '' || !isset($expected[$worksheetName][$cellAddress])) {
                        continue;
                    }

                    self::assertEquals($expected[$worksheetName][$cellAddress], $cell->getCellFormatString());
                }
            }
        }
    }

    #[Test]
    function it_reads_as_array_with_custom_formatting()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            ->customFormats([
                // use either format string or format ID as index
                // e.g. 'h:mm:ss' => static fn(DateTime $date) => $date->format('d.m.Y'),
                14 => static fn(DateTime $date) => $date->format('d.m.Y'),
                // 9 => static fn(int $number) => number_format($number * 100, 2) . '%',
                '0%' => static fn(int $number) => number_format($number * 100, 2) . '%',
                '#,##0.00' => static function(float $number, string $rawValue, string $cellAddress, string $worksheetName) {
                    // worksheet name and cell address are available in case formatting depends on the cell's location
                    if ($worksheetName === 'Sheet1' && $cellAddress === 'A8') {
                        return number_format($number, 2);
                    }
                    return $number;
                },
            ])
            ->readAsArray();

        self::assertTrue($reader->worksheetsHaveBeenLoaded());

        // formatted date as string
        self::assertIsString($data['Sheet1'][3]['A3']);
        self::assertEquals('01.04.2026', $data['Sheet1'][3]['A3']);

        // date time still a DateTime instance
        self::assertInstanceOf(DateTime::class, $data['Sheet1'][3]['B3']);
        self::assertEquals(new DateTime('18.03.1974 19:14:23'), $data['Sheet1'][3]['B3']);

        // percent value formatted as string
        self::assertEquals('100.00%', $data['Sheet1'][2]['D2']);

        // number formatted with thousands separator
        self::assertEquals('-123,456,789.54', $data['Sheet1'][8]['A8']);
    }
}
