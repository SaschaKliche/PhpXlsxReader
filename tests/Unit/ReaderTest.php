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
    function it_selects_multiple_worksheets_to_load()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader->worksheets(['Sheet1', 'Sheet2'])->readAsArray();

        // workbook contains three worksheets
        self::assertSame(['Sheet1', 'Sheet2', 'Table'], $reader->getWorksheetNames());

        // Sheet1 and Sheet2 have been loaded, Table has not been loaded
        self::assertSame(['Sheet1', 'Sheet2'], array_keys($data));
    }

    #[Test]
    function it_selects_a_single_worksheet_to_load()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader->worksheet('Sheet2')->readAsArray();

        // workbook contains three worksheets
        self::assertSame(['Sheet1', 'Sheet2', 'Table'], $reader->getWorksheetNames());

        // only Sheet2 has been loaded
        self::assertSame(['Sheet2'], array_keys($data));
    }

    #[Test]
    function it_reads_a_larger_file_using_generators()
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

        // simply opening the workbook does not load the worksheets
        self::assertFalse($reader->worksheetsHaveBeenLoaded());

        // workbook contains three worksheets
        // their names are accessible without loading data either all at once ...
        self::assertSame(['Sheet1', 'Sheet2', 'Table'], $reader->getWorksheetNames());
        // ... or by using the 1-based index ...
        self::assertSame('Sheet1', $reader->getWorksheetName(1));
        self::assertSame('Sheet2', $reader->getWorksheetName(2));
        self::assertSame('Table', $reader->getWorksheetName(3));
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
