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
    function it_loads_a_specific_row()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            ->worksheets(['Sheet2'])
            ->row(1)
            ->readAsArray();

        self::assertEquals(
            [
                'Sheet2' => [
                    1 => [
                        'A1' => 'The second sheet',
                        'B1' => 'is even more',
                        'C1' => 'interesting!',
                    ],
                ],
            ],
            $data
        );
    }

    #[Test]
    function it_loads_a_specific_cell_with_column_character()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            ->worksheets(['Sheet2'])
            ->rows([1])
            ->column('B')
            ->readAsArray();

        self::assertEquals(['Sheet2' => [1 => ['B1' => 'is even more']]], $data);
    }

    #[Test]
    function it_loads_requested_columns_only_from_each_worksheet_into_an_array()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            // these columns are requested from each worksheet
            // columns can be requested using their column index or character(s)
            ->columns([2, 'C', 4, 'E'])
            ->readAsArray();

        // "Sheet1" has rows 1, 2, 3, 4, 6, 7, 8 and columns A - D (1 - 4)
        // row 5 is missing, it is not returned because we did not request to include missing rows
        self::assertArrayHasKey('Sheet1', $data);
        self::assertEquals([1, 2, 3, 4, 6, 7, 8], array_keys($data['Sheet1'])); // rows

        // column A has not been requested so it is not returned for any of the rows
        // row 1 has A, B, C
        self::assertEquals(['B1', 'C1'], array_keys($data['Sheet1'][1]));
        // row 2 has A, B, C, D
        self::assertEquals(['B2', 'C2', 'D2'], array_keys($data['Sheet1'][2]));
        // row 3 has A, B
        self::assertEquals(['B3'], array_keys($data['Sheet1'][3]));
        // row 4 has A, C, it does not have columns B (2), D (4) and E (5)
        self::assertEquals(['C4'], array_keys($data['Sheet1'][4]));
        // rows 6 - 8 only have data in column A, so they are returned as empty rows
        self::assertEquals([], array_keys($data['Sheet1'][6]));
        self::assertEquals([], array_keys($data['Sheet1'][7]));
        self::assertEquals([], array_keys($data['Sheet1'][8]));

        // "Sheet2" has rows 1, 2 and columns A - C (1 - 3)
        // the column restrictions only apply to "Sheet1", so "Sheet2" is returned as is
        self::assertArrayHasKey('Sheet2', $data);
        self::assertEquals([1, 2], array_keys($data['Sheet2']));
        self::assertEquals(['B1', 'C1'], array_keys($data['Sheet2'][1]));
        // rows 2 only has data in column A so it will be returned as an empty row
        self::assertEquals([], array_keys($data['Sheet2'][2]));

        // "Tables" has rows 1 - 7 and columns A - C (1 - 3)
        self::assertArrayHasKey('Table', $data);
        self::assertEquals([1, 2, 3, 4, 5, 6, 7], array_keys($data['Table']));
        self::assertEquals(['B1', 'C1'], array_keys($data['Table'][1]));
        self::assertEquals(['B2', 'C2'], array_keys($data['Table'][2]));
        self::assertEquals(['B3', 'C3'], array_keys($data['Table'][3]));
        self::assertEquals(['B4', 'C4'], array_keys($data['Table'][4]));
        self::assertEquals(['B5', 'C5'], array_keys($data['Table'][5]));
        self::assertEquals(['B6', 'C6'], array_keys($data['Table'][6]));
        self::assertEquals(['B7', 'C7'], array_keys($data['Table'][7]));
    }

    #[Test]
    function it_loads_requested_columns_only_from_a_specific_worksheet_into_an_array_including_missing_columns()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            ->includeMissingCells()
            // these rows are only requested from worksheet "Sheet1", for other worksheets all rows will be returned
            ->columns(['Sheet1' => ['B', 'C', 4, 5]])
            ->readAsArray();

        // "Sheet1" has rows 1, 2, 3, 4, 6, 7, 8 and columns A - D (1 - 4)
        // row 5 is missing, it is not returned because we did not request to include missing rows
        self::assertArrayHasKey('Sheet1', $data);
        self::assertEquals([1, 2, 3, 4, 6, 7, 8], array_keys($data['Sheet1'])); // rows

        // column A has not been requested so it is not returned for any of the rows
        self::assertEquals(['B1', 'C1'], array_keys($data['Sheet1'][1]));
        self::assertEquals(['B2', 'C2', 'D2'], array_keys($data['Sheet1'][2]));
        self::assertEquals(['B3'], array_keys($data['Sheet1'][3]));
        // row 4 does not have columns 2 / 'B', but it was explicitly requested so it will be returned
        // row 4 does not have columns 4 and 5, they will not be returned even though we requested missing cells
        self::assertEquals(['B4', 'C4'], array_keys($data['Sheet1'][4]));
        // rows 6 - 8 only have data in column A, so they are returned as empty rows
        self::assertEquals([], array_keys($data['Sheet1'][6]));
        self::assertEquals([], array_keys($data['Sheet1'][7]));
        self::assertEquals([], array_keys($data['Sheet1'][8]));

        // "Sheet2" has rows 1, 2 and columns A - C (1 - 3)
        // the column restrictions only apply to "Sheet1", so "Sheet2" is returned as is
        self::assertArrayHasKey('Sheet2', $data);
        self::assertEquals([1, 2], array_keys($data['Sheet2']));
        self::assertEquals(['A1', 'B1', 'C1'], array_keys($data['Sheet2'][1]));
        // rows 2 only has data in column A
        self::assertEquals(['A2'], array_keys($data['Sheet2'][2]));

        // "Tables" has rows 1 - 7 and columns A - C (1 - 3)
        self::assertArrayHasKey('Table', $data);
        self::assertEquals([1, 2, 3, 4, 5, 6, 7], array_keys($data['Table']));
        self::assertEquals(['A1', 'B1', 'C1'], array_keys($data['Table'][1]));
        self::assertEquals(['A2', 'B2', 'C2'], array_keys($data['Table'][2]));
        self::assertEquals(['A3', 'B3', 'C3'], array_keys($data['Table'][3]));
        self::assertEquals(['A4', 'B4', 'C4'], array_keys($data['Table'][4]));
        self::assertEquals(['A5', 'B5', 'C5'], array_keys($data['Table'][5]));
        self::assertEquals(['A6', 'B6', 'C6'], array_keys($data['Table'][6]));
        self::assertEquals(['A7', 'B7', 'C7'], array_keys($data['Table'][7]));
    }

    #[Test]
    function it_loads_requested_rows_only_from_each_worksheet_into_an_array()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            // these rows are requested from all worksheets
            ->rows([2, 3, 4, 5])
            ->readAsArray();

        // "Sheet1" has rows 1, 6, 7, 8, but we did not request them so they are missing
        // rows 2 - 4 have been requested and are present in the file, they are returned
        // row 5 has been requested but does not exist in the file
        // it is not present because we did not request to include missing rows
        self::assertArrayHasKey('Sheet1', $data);
        self::assertEquals([2, 3, 4], array_keys($data['Sheet1']));

        // "Sheet2" only has rows 1 and 2, but row 1 has not been requested
        self::assertArrayHasKey('Sheet2', $data);
        self::assertEquals([2], array_keys($data['Sheet2']));

        // "Table" has rows 1 - 7
        self::assertArrayHasKey('Table', $data);
        self::assertEquals([2, 3, 4, 5], array_keys($data['Table']));
    }

    #[Test]
    function it_loads_requested_rows_only_from_a_specific_worksheet_into_an_array_including_missing_rows()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            ->includeMissingRows()
            // these rows are only requested from worksheet "Sheet1", for other worksheets all rows will be returned
            ->rows(['Sheet1' => [2, 3, 4, 5]])
            ->readAsArray();

        // "Sheet1" also has rows 1, 6, 7, 8, but we did not request them so they are missing
        // rows 2 - 4 have been requested and are present in the file, they are returned
        // row 5 has been requested but does not exist in the file
        // it is still present because we requested to include missing rows
        self::assertArrayHasKey('Sheet1', $data);
        self::assertEquals([2, 3, 4, 5], array_keys($data['Sheet1']));

        // "Sheet2" is present in the file, has not been requested with restrictions, is returned as-is
        // "Sheet2" only has rows 1 and 2
        self::assertArrayHasKey('Sheet2', $data);
        self::assertEquals([1, 2], array_keys($data['Sheet2']));

        // "Table" is present in the file, has not been requested with restrictions, is returned as-is
        self::assertArrayHasKey('Table', $data);
        self::assertEquals([1, 2, 3, 4, 5, 6, 7], array_keys($data['Table']));
    }

    #[Test]
    function it_loads_requested_rows_individually_by_worksheet_into_an_array()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            // each worksheet has its own row selection
            // we do not request missing rows so rows that don't exist on a worksheet will not be returned
            ->rows(['Sheet1' => [4, 5], 'Sheet2' => [2], 'Table' => [1, 3]])
            ->readAsArray();

        // "Sheet1" has rows 1, 2, 3, 4, 6, 7, 8 => we requested 4 and 5, 5 does not exist
        self::assertArrayHasKey('Sheet1', $data);
        self::assertEquals([4], array_keys($data['Sheet1']));

        // "Sheet2" only has rows 1 and 2 => we requested only 2 which exists
        self::assertArrayHasKey('Sheet2', $data);
        self::assertEquals([2], array_keys($data['Sheet2']));

        // "Table" has rows 1 - 7 => we requested 1 and 3, both exist
        self::assertArrayHasKey('Table', $data);
        self::assertEquals([1, 3], array_keys($data['Table']));
    }

    #[Test]
    function it_loads_rows_with_min_max_index_only_from_a_specific_worksheet_into_an_array()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            ->includeMissingRows()
            // these rows are only requested from worksheet "Sheet1", for other worksheets all rows will be returned
            ->rows(['Sheet1' => [Configuration::MIN => 2, Configuration::MAX => 5]])
            ->readAsArray();

        // "Sheet1" also has rows 1, 6, 7, 8, but we did not request them so they are missing
        // rows 2 - 4 have been requested and are present in the file, they are returned
        // row 5 has been requested but does not exist in the file
        // it is still present because we requested to include missing rows
        self::assertArrayHasKey('Sheet1', $data);
        self::assertEquals([2, 3, 4, 5], array_keys($data['Sheet1']));

        // "Sheet2" is present in the file, has not been requested with restrictions, is returned as-is
        // "Sheet2" only has rows 1 and 2
        self::assertArrayHasKey('Sheet2', $data);
        self::assertEquals([1, 2], array_keys($data['Sheet2']));

        // "Table" is present in the file, has not been requested with restrictions, is returned as-is
        self::assertArrayHasKey('Table', $data);
        self::assertEquals([1, 2, 3, 4, 5, 6, 7], array_keys($data['Table']));
    }

    #[Test]
    function it_loads_rows_with_min_index_only_from_a_specific_worksheet_into_an_array()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            // these rows are only requested from worksheet "Sheet1"
            // for other worksheets all rows will be returned
            ->rows(['Sheet1' => [Configuration::MIN => 3]])
            ->readAsArray();

        // "Sheet1" has rows 1 + 2, but we did not request them so they are missing
        // rows 3 - 4 have been requested and are present in the file
        // row 5 has been requested but does not exist in the file
        // rows 6 - 8 are present and requested
        self::assertArrayHasKey('Sheet1', $data);
        self::assertEquals([3, 4, 6, 7, 8], array_keys($data['Sheet1']));

        // "Sheet2" is present in the file, has not been requested with restrictions, is returned as-is
        // "Sheet2" only has rows 1 and 2
        self::assertArrayHasKey('Sheet2', $data);
        self::assertEquals([1, 2], array_keys($data['Sheet2']));

        // "Table" is present in the file, has not been requested with restrictions, is returned as-is
        self::assertArrayHasKey('Table', $data);
        self::assertEquals([1, 2, 3, 4, 5, 6, 7], array_keys($data['Table']));
    }

    #[Test]
    function it_loads_rows_with_min_index_only_from_a_all_worksheets_into_an_array()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            // these rows are requested from all worksheets
            ->rows([Configuration::MIN => 3])
            ->readAsArray();

        // "Sheet1" has rows 1 + 2, but we did not request them so they are missing
        // rows 3 - 4 have been requested and are present in the file
        // row 5 has been requested but does not exist in the file
        // rows 6 - 8 are present and requested
        self::assertArrayHasKey('Sheet1', $data);
        self::assertEquals([3, 4, 6, 7, 8], array_keys($data['Sheet1']));

        // "Sheet2" only has rows 1 and 2, it is an empty row but not a missing row, so it is still returned
        self::assertArrayHasKey('Sheet2', $data);
        self::assertEquals([], array_keys($data['Sheet2']));

        // "Table" has rows 1 - 7
        self::assertArrayHasKey('Table', $data);
        self::assertEquals([3, 4, 5, 6, 7], array_keys($data['Table']));
    }

    #[Test]
    function it_loads_rows_with_max_index_only_from_a_all_worksheets_into_an_array()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            // these rows are requested from all worksheets
            ->rows([Configuration::MAX => 3])
            ->readAsArray();

        // "Sheet1" has rows 1 - 8
        self::assertArrayHasKey('Sheet1', $data);
        self::assertEquals([1, 2, 3], array_keys($data['Sheet1']));

        // "Sheet2" only has rows 1 and 2
        self::assertArrayHasKey('Sheet2', $data);
        self::assertEquals([1, 2], array_keys($data['Sheet2']));

        // "Table" has rows 1 - 7
        self::assertArrayHasKey('Table', $data);
        self::assertEquals([1, 2, 3], array_keys($data['Table']));
    }
}
