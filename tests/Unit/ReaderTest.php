<?php /** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SaschaKliche\PhpXlsxReader\Tests\AbstractTestCase;
use SaschaKliche\PhpXlsxReader\XlsxReader;

class ReaderTest extends AbstractTestCase
{
    #[Test]
    function it_provides_a_version()
    {
        self::assertIsString(XlsxReader::VERSION);
    }

    #[Test]
    function it_selects_the_worksheets_to_load()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader->worksheets(['Sheet2'])->readAsArray();

        self::assertEquals(['Sheet1', 'Sheet2', 'Table'], $reader->getWorksheetNames());
        self::assertArrayNotHasKey('Sheet1', $data);
        self::assertArrayHasKey('Sheet2', $data);
        self::assertArrayNotHasKey('Table', $data);
    }

    #[Test]
    function it_reads_a_larger_file()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'LargerFile.xlsx');

        foreach ($reader->read() as $worksheetName => $rows) {
            self::assertIsString($worksheetName);

            foreach ($rows as $rowIndex => $row) {
                self::assertIsInt($rowIndex);

                foreach ($row as $cellAddress => $cell) {
                    self::assertIsString($cellAddress);
                }
            }
        }
    }

    #[Test]
    function it_reads_a_larger_file_as_array()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'LargerFile.xlsx');

        $data = $reader->readAsArray();

        self::assertArrayNotHasKey('Tabelle1', $data);
    }

    #[Test]
    function it_provides_access_to_worksheet_names_without_loading_the_worksheets()
    {
        $reader = new XlsxReader();

        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        self::assertFalse($reader->worksheetsHaveBeenLoaded());
        self::assertEquals(['Sheet1', 'Sheet2', 'Table'], $reader->getWorksheetNames());
    }

    #[Test]
    function it_detects_the_date_system()
    {
        $reader = new XlsxReader();

        $reader->open(self::INPUT_FILES_DIR . 'Dates1900.xlsx');
        self::assertFalse($reader->worksheetsHaveBeenLoaded());
        self::assertTrue($reader->dateSystem1900());

        $reader->open(self::INPUT_FILES_DIR . 'Dates1904.xlsx');
        self::assertFalse($reader->worksheetsHaveBeenLoaded());
        self::assertFalse($reader->dateSystem1900());
    }
}
