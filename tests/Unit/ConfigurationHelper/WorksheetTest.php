<?php /** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit\ConfigurationHelper;

use PHPUnit\Framework\Attributes\Test;
use SaschaKliche\PhpXlsxReader\Tests\AbstractTestCase;
use SaschaKliche\PhpXlsxReader\XlsxReader;

class WorksheetTest extends AbstractTestCase
{
    #[Test]
    function it_loads_data_from_a_single_worksheet_using_worksheet_name()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->worksheet('Sheet2')
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
            ->worksheet('Sheet2')
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
            ->worksheet(2)
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
            ->worksheet(2)
            ->useWorksheetIndexNumbers()
            ->readAsArray();

        self::assertSame(
            [2], // only data for the 2nd worksheet (Sheet2) is returned
            array_keys($data)
        );
    }
}
