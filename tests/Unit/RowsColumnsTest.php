<?php /** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SaschaKliche\PhpXlsxReader\Configuration;
use SaschaKliche\PhpXlsxReader\Tests\AbstractTestCase;
use SaschaKliche\PhpXlsxReader\XlsxReader;

class RowsColumnsTest extends AbstractTestCase
{
    #[Test]
    function it_loads_requested_columns_only_from_each_worksheet_into_an_array()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        // these rows are requested from each worksheet
        $data = $reader->columns([2, 3, 4, 5])->readAsArray();

        self::assertArrayHasKey(1, $data['Sheet1']);

        // the row 1 on worksheet "sheet1" has a cell A1 (first column) but it was not requested so it is missing
        self::assertArrayNotHasKey('A1', $data['Sheet1'][1]);
        self::assertArrayHasKey('B1', $data['Sheet1'][1]);
        self::assertArrayHasKey('C1', $data['Sheet1'][1]);
        // row 1 does not have columns 4 and 5, they will not be returned
        self::assertArrayNotHasKey('D1', $data['Sheet1'][1]);
        self::assertArrayNotHasKey('E1', $data['Sheet1'][1]);
        self::assertArrayNotHasKey('A2', $data['Sheet1'][2]);
        self::assertArrayHasKey('B2', $data['Sheet1'][2]);
        self::assertArrayHasKey('C2', $data['Sheet1'][2]);
        self::assertArrayHasKey('D2', $data['Sheet1'][2]);

        // the first row on worksheet "Sheet1" has columns 1 - 3 (A - C)
        self::assertArrayNotHasKey('A1', $data['Sheet2'][1]);
        self::assertArrayHasKey('B1', $data['Sheet2'][1]);
        self::assertArrayHasKey('C1', $data['Sheet2'][1]);
        self::assertArrayNotHasKey('D1', $data['Sheet2'][1]);
        self::assertArrayNotHasKey('E1', $data['Sheet2'][1]);
        // on the second row of worksheet "Sheet2" only the first column exists so we get an empty row
        self::assertArrayNotHasKey('A2', $data['Sheet2'][2]);
        self::assertArrayNotHasKey('B2', $data['Sheet2'][2]);
        self::assertArrayNotHasKey('C2', $data['Sheet2'][2]);
        self::assertArrayNotHasKey('D2', $data['Sheet2'][2]);
        self::assertArrayNotHasKey('E2', $data['Sheet2'][2]);
    }

    #[Test]
    function it_loads_requested_columns_only_from_a_specific_worksheet_into_an_array_including_missing_columns()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        // these rows are only requested from worksheet "Sheet1", for other worksheets all rows will be returned
        $data = $reader->includeMissingCells()->columns(['Sheet1' => [2, 3, 4, 5]])->readAsArray();

        self::assertArrayNotHasKey('A4', $data['Sheet1'][4]);
        // row 4 does not have columns 2 but it will be returned
        self::assertArrayHasKey('B4', $data['Sheet1'][4]);
        self::assertArrayHasKey('C4', $data['Sheet1'][4]);
        // row 4 does not have columns 4 and 5, they will not be returned
        self::assertArrayNotHasKey('D4', $data['Sheet1'][4]);
        self::assertArrayNotHasKey('E4', $data['Sheet1'][4]);
    }

    #[Test]
    function it_loads_requested_rows_only_from_each_worksheet_into_an_array()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        // these rows are requested from each worksheet
        $data = $reader->rows([2, 3, 4, 5])->readAsArray();

        // the worksheet "sheet1" has a row 1, but we did not request it so it is missing
        self::assertArrayNotHasKey(1, $data['Sheet1']);
        // rows 2 - 4 have been requested and are present in the file
        self::assertArrayHasKey(2, $data['Sheet1']);
        self::assertArrayHasKey(3, $data['Sheet1']);
        self::assertArrayHasKey(4, $data['Sheet1']);
        // row 5 has been requested but does not exist in the file
        self::assertArrayNotHasKey(5, $data['Sheet1']);

        // the worksheet "sheet2" only has rows 1 and 2, but row 1 has not been requested
        self::assertArrayNotHasKey(1, $data['Sheet2']);
        self::assertArrayHasKey(2, $data['Sheet2']);
        self::assertArrayNotHasKey(3, $data['Sheet2']);
        self::assertArrayNotHasKey(4, $data['Sheet2']);
        self::assertArrayNotHasKey(5, $data['Sheet2']);

        // the worksheet "Table" has all requested rows
        self::assertArrayNotHasKey(1, $data['Table']);
        self::assertArrayHasKey(2, $data['Table']);
        self::assertArrayHasKey(3, $data['Table']);
        self::assertArrayHasKey(4, $data['Table']);
        self::assertArrayHasKey(5, $data['Table']);
    }

    #[Test]
    function it_loads_requested_rows_only_from_a_specific_worksheet_into_an_array_including_missing_rows()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        // these rows are only requested from worksheet "Sheet1", for other worksheets all rows will be returned
        $data = $reader->includeMissingRows()->rows(['Sheet1' => [2, 3, 4, 5]])->readAsArray();

        // the worksheet "sheet1" has a row 1, but we did not request it so it is missing
        self::assertArrayNotHasKey(1, $data['Sheet1']);
        // rows 2 - 4 have been requested and are present in the file
        self::assertArrayHasKey(2, $data['Sheet1']);
        self::assertArrayHasKey(3, $data['Sheet1']);
        self::assertArrayHasKey(4, $data['Sheet1']);
        // row 5 has been requested but does not exist in the file, it is still present because we requested to include missing rows
        self::assertArrayHasKey(5, $data['Sheet1']);

        // "Sheet2" only has rows 1 and 2
        self::assertArrayHasKey(1, $data['Sheet2']);
        self::assertArrayHasKey(2, $data['Sheet2']);
        self::assertArrayNotHasKey(3, $data['Sheet2']);
        self::assertArrayNotHasKey(4, $data['Sheet2']);
        self::assertArrayNotHasKey(5, $data['Sheet2']);

        self::assertArrayHasKey(1, $data['Table']);
        self::assertArrayHasKey(2, $data['Table']);
        self::assertArrayHasKey(3, $data['Table']);
        self::assertArrayHasKey(4, $data['Table']);
        self::assertArrayHasKey(5, $data['Table']);
    }

    #[Test]
    function it_loads_rows_with_min_max_index_only_from_a_specific_worksheet_into_an_array()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        // these rows are only requested from worksheet "Sheet1", for other worksheets all rows will be returned
        $data = $reader->includeMissingRows()->rows(['Sheet1' => [Configuration::MIN => 2, Configuration::MAX => 5]])->readAsArray();

        // the worksheet "sheet1" has a row 1, but we did not request it so it is missing
        self::assertArrayNotHasKey(1, $data['Sheet1']);
        // rows 2 - 4 have been requested and are present in the file
        self::assertArrayHasKey(2, $data['Sheet1']);
        self::assertArrayHasKey(3, $data['Sheet1']);
        self::assertArrayHasKey(4, $data['Sheet1']);
        // row 5 has been requested but does not exist in the file, it is still present because we requested to include missing rows
        self::assertArrayHasKey(5, $data['Sheet1']);

        // "Sheet2" only has rows 1 and 2
        self::assertArrayHasKey(1, $data['Sheet2']);
        self::assertArrayHasKey(2, $data['Sheet2']);
        self::assertArrayNotHasKey(3, $data['Sheet2']);
        self::assertArrayNotHasKey(4, $data['Sheet2']);
        self::assertArrayNotHasKey(5, $data['Sheet2']);

        self::assertArrayHasKey(1, $data['Table']);
        self::assertArrayHasKey(2, $data['Table']);
        self::assertArrayHasKey(3, $data['Table']);
        self::assertArrayHasKey(4, $data['Table']);
        self::assertArrayHasKey(5, $data['Table']);
    }

    #[Test]
    function it_loads_rows_with_min_index_only_from_a_specific_worksheet_into_an_array()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        // these rows are only requested from worksheet "Sheet1", for other worksheets all rows will be returned
        $data = $reader->rows(['Sheet1' => [Configuration::MIN => 3]])->readAsArray();

        // the worksheet "sheet1" has rows 1 + 2, but we did not request them so they are missing
        self::assertArrayNotHasKey(1, $data['Sheet1']);
        self::assertArrayNotHasKey(2, $data['Sheet1']);
        // rows 3 - 4 have been requested and are present in the file
        self::assertArrayHasKey(3, $data['Sheet1']);
        self::assertArrayHasKey(4, $data['Sheet1']);
        // row 5 has been requested but does not exist in the file
        self::assertArrayNotHasKey(5, $data['Sheet1']);

        // "Sheet2" only has rows 1 and 2
        self::assertArrayHasKey(1, $data['Sheet2']);
        self::assertArrayHasKey(2, $data['Sheet2']);
        self::assertArrayNotHasKey(3, $data['Sheet2']);
        self::assertArrayNotHasKey(4, $data['Sheet2']);
        self::assertArrayNotHasKey(5, $data['Sheet2']);

        self::assertArrayHasKey(1, $data['Table']);
        self::assertArrayHasKey(2, $data['Table']);
        self::assertArrayHasKey(3, $data['Table']);
        self::assertArrayHasKey(4, $data['Table']);
        self::assertArrayHasKey(5, $data['Table']);
    }
}
