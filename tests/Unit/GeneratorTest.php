<?php /** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit;

use Generator;
use PHPUnit\Framework\Attributes\Test;
use SaschaKliche\PhpXlsxReader\Tests\AbstractTestCase;
use SaschaKliche\PhpXlsxReader\XlsxReader;

class GeneratorTest extends AbstractTestCase
{
    #[Test]
    function it_reads_using_generator_with_cell_addresses()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->useCellAddress();

        foreach ($reader->read() as $worksheetName => $rows) {
            self::assertIsString($worksheetName);
            self::assertInstanceOf(Generator::class, $rows);

            foreach ($rows as $rowIndex => $row) {
                self::assertIsInt($rowIndex);
                self::assertIsArray($row);

                foreach ($row as $cellAddress => $cell) {
                    self::assertIsString($cellAddress);
                }
            }
        }

        self::assertTrue($reader->worksheetsHaveBeenLoaded());
    }

    #[Test]
    function it_reads_using_generator_with_column_indexes()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->useColumnIndex();

        foreach ($reader->read() as $worksheetName => $rows) {
            self::assertIsString($worksheetName);

            foreach ($rows as $rowIndex => $row) {
                self::assertIsInt($rowIndex);

                foreach ($row as $columnIndex => $cell) {
                    self::assertIsInt($columnIndex);
                }
            }
        }
    }
}
