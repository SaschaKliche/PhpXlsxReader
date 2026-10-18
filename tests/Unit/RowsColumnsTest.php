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
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->worksheet('Sheet2')
            ->row(1)
            ->readAsArray();

        self::assertSame(
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
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->worksheet('Sheet2')
            ->rows([1])
            ->column('B')
            ->readAsArray();

        self::assertSame(['Sheet2' => [1 => ['B1' => 'is even more']]], $data);
    }

    #[Test]
    function it_loads_requested_columns_only_from_each_worksheet_into_an_array()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            // these columns are requested from each worksheet
            // columns can be requested using their column index or character(s)
            ->columns([2, 'C', 4, 'E'])
            ->readAsArray();

        self::assertSame(['Sheet1', 'Sheet2', 'Table'], array_keys($data));

        // "Sheet1" has rows 1, 2, 3, 4, 6, 7, 8 and columns A - D (1 - 4)
        // row 5 is missing, it is not returned because we did not request to include missing rows
        self::assertSame([1, 2, 3, 4, 6, 7, 8], array_keys($data['Sheet1'])); // rows

        // column A has not been requested so it is not returned for any of the rows
        // row 1 has A, B, C
        self::assertSame(['B1', 'C1'], array_keys($data['Sheet1'][1]));
        // row 2 has A, B, C, D
        self::assertSame(['B2', 'C2', 'D2'], array_keys($data['Sheet1'][2]));
        // row 3 has A, B
        self::assertSame(['B3'], array_keys($data['Sheet1'][3]));
        // row 4 has A, C, it does not have columns B (2), D (4) and E (5)
        self::assertSame(['C4'], array_keys($data['Sheet1'][4]));
        // rows 6 - 8 only have data in column A, so they are returned as empty rows
        self::assertSame([], array_keys($data['Sheet1'][6]));
        self::assertSame([], array_keys($data['Sheet1'][7]));
        self::assertSame([], array_keys($data['Sheet1'][8]));

        // "Sheet2" has rows 1, 2 and columns A - C (1 - 3)
        // the column restrictions only apply to "Sheet1", so "Sheet2" is returned as is
        self::assertSame([1, 2], array_keys($data['Sheet2']));
        self::assertSame(['B1', 'C1'], array_keys($data['Sheet2'][1]));
        // rows 2 only has data in column A so it will be returned as an empty row
        self::assertSame([], array_keys($data['Sheet2'][2]));

        // "Tables" has rows 1 - 7 and columns A - C (1 - 3)
        self::assertSame([1, 2, 3, 4, 5, 6, 7], array_keys($data['Table']));
        self::assertSame(['B1', 'C1'], array_keys($data['Table'][1]));
        self::assertSame(['B2', 'C2'], array_keys($data['Table'][2]));
        self::assertSame(['B3', 'C3'], array_keys($data['Table'][3]));
        self::assertSame(['B4', 'C4'], array_keys($data['Table'][4]));
        self::assertSame(['B5', 'C5'], array_keys($data['Table'][5]));
        self::assertSame(['B6', 'C6'], array_keys($data['Table'][6]));
        self::assertSame(['B7', 'C7'], array_keys($data['Table'][7]));
    }

    #[Test]
    function it_loads_requested_columns_only_from_a_specific_worksheet_into_an_array_including_missing_columns()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->includeMissingCells()
            // these rows are only requested from worksheet "Sheet1", for other worksheets all rows will be returned
            ->columns(['Sheet1' => ['B', 'c', 4, 5]])
            ->readAsArray();

        self::assertSame(['Sheet1', 'Sheet2', 'Table'], array_keys($data));

        // "Sheet1" has rows 1, 2, 3, 4, 6, 7, 8 and columns A - D (1 - 4)
        // row 5 is missing, it is not returned because we did not request to include missing rows
        self::assertSame([1, 2, 3, 4, 6, 7, 8], array_keys($data['Sheet1'])); // rows

        // column A has not been requested so it is not returned for any of the rows
        self::assertSame(['B1', 'C1'], array_keys($data['Sheet1'][1]));
        self::assertSame(['B2', 'C2', 'D2'], array_keys($data['Sheet1'][2]));
        self::assertSame(['B3'], array_keys($data['Sheet1'][3]));
        // row 4 does not have columns 2 / 'B', but it was explicitly requested so it will be returned
        // row 4 does not have columns 4 and 5, they will not be returned even though we requested missing cells
        self::assertSame(['B4', 'C4'], array_keys($data['Sheet1'][4]));
        // rows 6 - 8 only have data in column A, so they are returned as empty rows
        self::assertSame([], array_keys($data['Sheet1'][6]));
        self::assertSame([], array_keys($data['Sheet1'][7]));
        self::assertSame([], array_keys($data['Sheet1'][8]));

        // "Sheet2" has rows 1, 2 and columns A - C (1 - 3)
        // the column restrictions only apply to "Sheet1", so "Sheet2" is returned as is
        self::assertSame([1, 2], array_keys($data['Sheet2']));
        self::assertSame(['A1', 'B1', 'C1'], array_keys($data['Sheet2'][1]));
        // rows 2 only has data in column A
        self::assertSame(['A2'], array_keys($data['Sheet2'][2]));

        // "Tables" has rows 1 - 7 and columns A - C (1 - 3)
        self::assertSame([1, 2, 3, 4, 5, 6, 7], array_keys($data['Table']));
        self::assertSame(['A1', 'B1', 'C1'], array_keys($data['Table'][1]));
        self::assertSame(['A2', 'B2', 'C2'], array_keys($data['Table'][2]));
        self::assertSame(['A3', 'B3', 'C3'], array_keys($data['Table'][3]));
        self::assertSame(['A4', 'B4', 'C4'], array_keys($data['Table'][4]));
        self::assertSame(['A5', 'B5', 'C5'], array_keys($data['Table'][5]));
        self::assertSame(['A6', 'B6', 'C6'], array_keys($data['Table'][6]));
        self::assertSame(['A7', 'B7', 'C7'], array_keys($data['Table'][7]));
    }

    #[Test]
    function it_loads_requested_rows_only_from_each_worksheet_into_an_array()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            // these rows are requested from all worksheets
            ->rows([2, 3, 4, 5])
            ->readAsArray();

        self::assertSame(['Sheet1', 'Sheet2', 'Table'], array_keys($data));

        // "Sheet1" has rows 1, 6, 7, 8, but we did not request them so they are missing
        // rows 2 - 4 have been requested and are present in the file, they are returned
        // row 5 has been requested but does not exist in the file
        // it is not present because we did not request to include missing rows
        self::assertSame([2, 3, 4], array_keys($data['Sheet1']));

        // "Sheet2" only has rows 1 and 2, but row 1 has not been requested
        self::assertSame([2], array_keys($data['Sheet2']));

        // "Table" has rows 1 - 7
        self::assertSame([2, 3, 4, 5], array_keys($data['Table']));
    }

    #[Test]
    function it_loads_requested_rows_only_from_a_specific_worksheet_into_an_array_including_missing_rows()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->includeMissingRows()
            // these rows are only requested from worksheet "Sheet1", for other worksheets all rows will be returned
            ->rows(['Sheet1' => [2, 3, 4, 5]])
            ->readAsArray();

        self::assertSame(['Sheet1', 'Sheet2', 'Table'], array_keys($data));

        // "Sheet1" also has rows 1, 6, 7, 8, but we did not request them so they are missing
        // rows 2 - 4 have been requested and are present in the file, they are returned
        // row 5 has been requested but does not exist in the file
        // it is still present because we requested to include missing rows
        self::assertSame([2, 3, 4, 5], array_keys($data['Sheet1']));

        // "Sheet2" is present in the file, has not been requested with restrictions, is returned as-is
        // "Sheet2" only has rows 1 and 2
        self::assertSame([1, 2], array_keys($data['Sheet2']));

        // "Table" is present in the file, has not been requested with restrictions, is returned as-is
        self::assertSame([1, 2, 3, 4, 5, 6, 7], array_keys($data['Table']));
    }

    #[Test]
    function it_loads_requested_rows_individually_by_worksheet_into_an_array()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            // each worksheet has its own row selection
            // we do not request missing rows so rows that don't exist on a worksheet will not be returned
            ->rows(['Sheet1' => [4, 5], 'Sheet2' => [2], 'Table' => [1, 3]])
            ->readAsArray();

        self::assertSame(['Sheet1', 'Sheet2', 'Table'], array_keys($data));

        // "Sheet1" has rows 1, 2, 3, 4, 6, 7, 8 => we requested 4 and 5, 5 does not exist
        self::assertSame([4], array_keys($data['Sheet1']));

        // "Sheet2" only has rows 1 and 2 => we requested only 2 which exists
        self::assertSame([2], array_keys($data['Sheet2']));

        // "Table" has rows 1 - 7 => we requested 1 and 3, both exist
        self::assertSame([1, 3], array_keys($data['Table']));
    }

    #[Test]
    function it_loads_rows_with_min_max_index_only_from_a_specific_worksheet_into_an_array()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->includeMissingRows()
            // these rows are only requested from worksheet "Sheet1", for other worksheets all rows will be returned
            ->rows(['Sheet1' => [Configuration::MIN => 2, Configuration::MAX => 5]])
            ->readAsArray();

        self::assertSame(['Sheet1', 'Sheet2', 'Table'], array_keys($data));

        // "Sheet1" also has rows 1, 6, 7, 8, but we did not request them so they are missing
        // rows 2 - 4 have been requested and are present in the file, they are returned
        // row 5 has been requested but does not exist in the file
        // it is still present because we requested to include missing rows
        self::assertSame([2, 3, 4, 5], array_keys($data['Sheet1']));

        // "Sheet2" is present in the file, has not been requested with restrictions, is returned as-is
        // "Sheet2" only has rows 1 and 2
        self::assertSame([1, 2], array_keys($data['Sheet2']));

        // "Table" is present in the file, has not been requested with restrictions, is returned as-is
        self::assertSame([1, 2, 3, 4, 5, 6, 7], array_keys($data['Table']));
    }

    #[Test]
    function it_loads_rows_with_min_index_only_from_a_specific_worksheet_into_an_array()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            // these rows are only requested from worksheet "Sheet1"
            // for other worksheets all rows will be returned
            ->rows(['Sheet1' => [Configuration::MIN => 3]])
            ->readAsArray();

        self::assertSame(['Sheet1', 'Sheet2', 'Table'], array_keys($data));

        // "Sheet1" has rows 1 + 2, but we did not request them so they are missing
        // rows 3 - 4 have been requested and are present in the file
        // row 5 has been requested but does not exist in the file
        // rows 6 - 8 are present and requested
        self::assertSame([3, 4, 6, 7, 8], array_keys($data['Sheet1']));

        // "Sheet2" is present in the file, has not been requested with restrictions, is returned as-is
        // "Sheet2" only has rows 1 and 2
        self::assertSame([1, 2], array_keys($data['Sheet2']));

        // "Table" is present in the file, has not been requested with restrictions, is returned as-is
        self::assertSame([1, 2, 3, 4, 5, 6, 7], array_keys($data['Table']));
    }

    #[Test]
    function it_loads_rows_with_min_index_only_from_a_all_worksheets_into_an_array()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            // these rows are requested from all worksheets
            ->rows([Configuration::MIN => 3])
            ->readAsArray();

        self::assertSame(['Sheet1', 'Sheet2', 'Table'], array_keys($data));

        // "Sheet1" has rows 1 + 2, but we did not request them so they are missing
        // rows 3 - 4 have been requested and are present in the file
        // row 5 has been requested but does not exist in the file
        // rows 6 - 8 are present and requested
        self::assertSame([3, 4, 6, 7, 8], array_keys($data['Sheet1']));

        // "Sheet2" only has rows 1 and 2, no rows are returned for that worksheet
        self::assertSame([], array_keys($data['Sheet2']));

        // "Table" has rows 1 - 7
        self::assertSame([3, 4, 5, 6, 7], array_keys($data['Table']));
    }

    #[Test]
    function it_loads_rows_with_max_index_only_from_a_all_worksheets_into_an_array()
    {
        $data = XlsxReader::from(self::INPUT_FILES_DIR . 'Basic.xlsx')
            // these rows are requested from all worksheets
            ->rows([Configuration::MAX => 3])
            ->readAsArray();

        self::assertSame(['Sheet1', 'Sheet2', 'Table'], array_keys($data));

        // "Sheet1" has rows 1 - 8
        self::assertSame([1, 2, 3], array_keys($data['Sheet1']));

        // "Sheet2" only has rows 1 and 2
        self::assertSame([1, 2], array_keys($data['Sheet2']));

        // "Table" has rows 1 - 7
        self::assertSame([1, 2, 3], array_keys($data['Table']));
    }
}
