<?php /** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit\ConfigurationHelper;

use PHPUnit\Framework\Attributes\Test;
use SaschaKliche\PhpXlsxReader\Tests\AbstractTestCase;
use SaschaKliche\PhpXlsxReader\XlsxReader;

class WorksheetsTest extends AbstractTestCase
{
    #[Test]
    function it_loads_data_from_a_single_worksheet_using_worksheet_name()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->worksheets(['Sheet2'])
            ->readAsArray();

        self::assertSame(
            // indexed by worksheet name by default
            ['Sheet2'], // only data for Sheet2 is returned
            array_keys($data)
        );
    }

    #[Test]
    function it_loads_data_from_a_single_worksheet_using_worksheet_name_returning_index_number()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->worksheets(['Sheet2'])
            ->useWorksheetIndexNumbers()
            ->readAsArray();

        self::assertSame(
            // indexed by 1-based worksheet index number
            [2], // only data for Sheet2 is returned
            array_keys($data)
        );
    }

    #[Test]
    function it_loads_data_from_a_single_worksheet_using_index_number()
    {
        $reader = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $expectedWorksheetName = $reader->getWorksheetName(2);
        self::assertSame('Sheet2', $expectedWorksheetName);

        $data = $reader
            ->worksheets([2])
            ->readAsArray();

        self::assertSame(
            // indexed by worksheet name by default
            [$expectedWorksheetName], // only data for the 2nd worksheet (Sheet2) is returned
            array_keys($data)
        );
    }

    #[Test]
    function it_loads_data_from_a_single_worksheet_using_index_number_returning_index_number()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->worksheets([2])
            ->useWorksheetIndexNumbers()
            ->readAsArray();

        self::assertSame(
            // indexed by 1-based worksheet index number
            [2], // only data for the 2nd worksheet (Sheet2) is returned
            array_keys($data)
        );
    }

    #[Test]
    function it_loads_data_from_multiple_worksheets_using_worksheet_names()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->worksheets(['Sheet1', 'Table'])
            ->readAsArray();

        self::assertSame(
            // indexed by worksheet name by default
            ['Sheet1', 'Table'],
            array_keys($data)
        );
    }

    #[Test]
    function it_loads_data_from_multiple_worksheets_using_worksheet_names_returning_index_number()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->worksheets(['Sheet1', 'Table'])
            ->useWorksheetIndexNumbers()
            ->readAsArray();

        self::assertSame(
            // indexed by 1-based worksheet index number
            [1, 3],
            array_keys($data)
        );
    }

    #[Test]
    function it_loads_data_from_multiple_worksheets_using_index_numbers()
    {
        $reader = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $expectedWorksheetName1 = $reader->getWorksheetName(1);
        self::assertSame('Sheet1', $expectedWorksheetName1);
        $expectedWorksheetName3 = $reader->getWorksheetName(3);
        self::assertSame('Table', $expectedWorksheetName3);

        $data = $reader
            ->worksheets([1, 3])
            ->readAsArray();

        self::assertSame(
            // indexed by worksheet name by default
            [$expectedWorksheetName1, $expectedWorksheetName3],
            array_keys($data)
        );
    }

    #[Test]
    function it_loads_data_from_multiple_worksheets_using_index_numbers_returning_index_number()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->worksheets([1, 3])
            ->useWorksheetIndexNumbers()
            ->readAsArray();

        self::assertSame(
            // indexed by 1-based worksheet index number
            [1, 3],
            array_keys($data)
        );
    }

    #[Test]
    function it_loads_data_from_multiple_worksheets_using_both()
    {
        $reader = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $expectedWorksheetName1 = $reader->getWorksheetName(1);
        self::assertSame('Sheet1', $expectedWorksheetName1);

        $data = $reader
            ->worksheets([1, 'Table'])
            ->readAsArray();

        self::assertSame(
            // indexed by worksheet name by default
            [$expectedWorksheetName1, 'Table'],
            array_keys($data)
        );
    }

    #[Test]
    function it_loads_data_from_multiple_worksheets_using_both_returning_index_number()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->worksheets([1, 'Table'])
            ->useWorksheetIndexNumbers()
            ->readAsArray();

        self::assertSame(
            // indexed by 1-based worksheet index number
            [1, 3],
            array_keys($data)
        );
    }
}
