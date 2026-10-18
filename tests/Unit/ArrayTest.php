<?php /** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit;

use DateTime;
use PHPUnit\Framework\Attributes\Test;
use SaschaKliche\PhpXlsxReader\Tests\AbstractTestCase;
use SaschaKliche\PhpXlsxReader\XlsxReader;

class ArrayTest extends AbstractTestCase
{
    #[Test]
    function it_reads_as_array_with_column_addresses()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        /*
         * The workbook contains three worksheets, so $data contains three entries:
         * [
         *   // first level is the worksheet name
         *   'Sheet1' => [
         *     // second level is the row number
         *     1 => [
         *       // third level is either the cell address or the column number as index and the cell's content as value
         *       'A1' => 'This is some',
         *       'B1' => 'very interesting',
         *       'C1' => 'data to look at.',
         *     ],
         *     ... // 2, 3, 4, 6, 7 (note: 5 is missing)
         *     8 => [
         *       'A8' => -123456789.54,
         *     ],
         *   ],
         *   'Sheet2' => [
         *     1 => [
         *       ...
         *     ],
         *     2 => [
         *       ...
         *     ],
         *   ],
         *   'Table' => [
         *     1 => [
         *       ...
         *     ],
         *     ... // 2, 3, 4, 5, 6
         *     7 => [
         *       ...
         *     ],
         *   ],
         * ]
         */
        $data = $reader
            ->skipMissingCells()
            ->skipMissingRows()
            ->readAsArray();

        foreach ($data as $worksheetName => $rows) {
            self::assertIsString($worksheetName);
            self::assertIsArray($rows);

            foreach ($rows as $rowIndex => $row) {
                self::assertIsInt($rowIndex);
                self::assertIsArray($row);

                foreach ($row as $cellAddress => $cell) {
                    self::assertIsString($cellAddress);
                }
            }
        }

        // the workbook contains three worksheets: 'Sheet1', 'Sheet2', 'Table'
        self::assertSame(['Sheet1', 'Sheet2', 'Table'], array_keys($data));

        // the worksheet "Sheet1" has rows 1 - 8 but row 5 is missing
        self::assertSame([1, 2, 3, 4, 6, 7, 8], array_keys($data['Sheet1']));

        // 1st row has cells A1, B1, C1; the cells are indexed by their addresses
        self::assertSame(['A1', 'B1', 'C1'], array_keys($data['Sheet1'][1]));

        // all cells in the 1st row contain string values
        self::assertSame('This is some', $data['Sheet1'][1]['A1']);
        self::assertSame('very interesting', $data['Sheet1'][1]['B1']);
        self::assertSame('data to look at.', $data['Sheet1'][1]['C1']);

        // 2nd row has cells A2, B2, C2, D2
        self::assertSame(['A2', 'B2', 'C2', 'D2'], array_keys($data['Sheet1'][2]));
        self::assertSame(1, $data['Sheet1'][2]['A2']); // positive integer
        self::assertSame(-2, $data['Sheet1'][2]['B2']); // negative integer
        self::assertSame(3.14, $data['Sheet1'][2]['C2']); // float
        self::assertSame(1, $data['Sheet1'][2]['D2']); // 1, displayed as 100% percent in the application

        // 3rd row has cells A3, B3
        self::assertSame(['A3', 'B3'], array_keys($data['Sheet1'][3]));
        self::assertEquals(new DateTime('01.04.2026'), $data['Sheet1'][3]['A3']);
        self::assertEquals(new DateTime('18.03.1974 19:14:23'), $data['Sheet1'][3]['B3']);

        // the 4th row does not contain consecutive cells, it has cells A4 and C4, cell B4 is missing
        self::assertSame(['A4', 'C4'], array_keys($data['Sheet1'][4]));
        // the cell A4 contains a formula, we return the previously computed value that is stored within the file
        self::assertSame(2.14, $data['Sheet1'][4]['A4']);
        self::assertSame('Skipped cell to the left', $data['Sheet1'][4]['C4']);

        // 6th row has a single cell, A6
        self::assertSame(['A6'], array_keys($data['Sheet1'][6]));
        self::assertSame('Skipped row above', $data['Sheet1'][6]['A6']);

        // 7th row has a single cell, A7, contains umlauts and euro sign
        self::assertSame(['A7'], array_keys($data['Sheet1'][7]));
        self::assertSame('W€ love ümläüts, don\'t we?', $data['Sheet1'][7]['A7']);

        // the worksheet "Sheet2" has rows 1 and 2
        // this worksheet has fixed rows and columns which doesn't affect reading the values
        self::assertSame([1, 2], array_keys($data['Sheet2']));

        // 1st row has cells A1, B1, C1
        self::assertSame(['A1', 'B1', 'C1'], array_keys($data['Sheet2'][1]));
        self::assertSame('The second sheet', $data['Sheet2'][1]['A1']);
        self::assertSame('is even more', $data['Sheet2'][1]['B1']);
        self::assertSame('interesting!', $data['Sheet2'][1]['C1']);

        // 2nd row has a single cell, A1
        self::assertSame(['A2'], array_keys($data['Sheet2'][2]));
        // the cell A2 contains a hyperlink, we return the display value
        self::assertSame('Github', $data['Sheet2'][2]['A2']);

        // the workbook has a final worksheet called "Table"
        // this worksheet has a table containing the data which doesn't affect reading the values
        // this worksheet also contains an image which is ignored
        self::assertSame([1, 2, 3, 4, 5, 6, 7], array_keys($data['Table']));

        // 1st row
        self::assertSame(['A1', 'B1', 'C1'], array_keys($data['Table'][1]));
        self::assertSame('Column A', $data['Table'][1]['A1']);
        self::assertSame('Column B', $data['Table'][1]['B1']);
        self::assertSame('Column C', $data['Table'][1]['C1']);

        // 2nd row
        self::assertSame(['A2', 'B2', 'C2'], array_keys($data['Table'][2]));
        self::assertSame(10, $data['Table'][2]['A2']);
        self::assertSame(-42, $data['Table'][2]['B2']);
        self::assertSame(0.001, $data['Table'][2]['C2']);

        // 3rd row
        self::assertSame(['A3', 'B3', 'C3'], array_keys($data['Table'][3]));
        self::assertSame(20, $data['Table'][3]['A3']);
        self::assertSame(58.12, $data['Table'][3]['B3']);
        self::assertSame(1, $data['Table'][3]['C3']);

        // 4th row
        self::assertSame(['A4', 'B4', 'C4'], array_keys($data['Table'][4]));
        self::assertSame(30, $data['Table'][4]['A4']);
        self::assertSame(9.837844, $data['Table'][4]['B4']);
        self::assertSame(2, $data['Table'][4]['C4']);

        // 5th row
        self::assertSame(['A5', 'B5', 'C5'], array_keys($data['Table'][5]));
        self::assertSame(40, $data['Table'][5]['A5']);
        self::assertSame(-273.14, $data['Table'][5]['B5']);
        self::assertSame(4, $data['Table'][5]['C5']);

        // 6th row
        self::assertSame(['A6', 'B6', 'C6'], array_keys($data['Table'][6]));
        self::assertSame(50, $data['Table'][6]['A6']);
        self::assertSame(99.99, $data['Table'][6]['B6']);
        self::assertSame(8, $data['Table'][6]['C6']);

        // the 7th row is a formula row in the table
        // the calculated values are returned like for other cells containing a formula
        self::assertSame(['A7', 'B7', 'C7'], array_keys($data['Table'][7]));
        self::assertSame(30, $data['Table'][7]['A7']);
        self::assertSame(-147.192156, $data['Table'][7]['B7']);
        self::assertSame(15.001, $data['Table'][7]['C7']);

        // we didn't use readWithHeader() so we should not have any headers
        self::assertEmpty($reader->getHeaders());
    }

    #[Test]
    function it_reads_as_array_with_column_addresses_and_includes_missing_cells_and_rows()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            ->includeMissingCells()
            ->includeMissingRows()
            ->readAsArray();

        // the workbook contains three worksheets: 'Sheet1', 'Sheet2', 'Table'
        self::assertSame(['Sheet1', 'Sheet2', 'Table'], array_keys($data));

        // the worksheet "Sheet1" has rows 1 - 8, the missing row 5 is included because missing rows have been requested
        self::assertSame([1, 2, 3, 4, 5, 6, 7, 8], array_keys($data['Sheet1']));

        // 1st row has cells A1, B1, C1
        self::assertSame(['A1', 'B1', 'C1'], array_keys($data['Sheet1'][1]));

        // 2nd row has cells A2, B2, C2, D2
        self::assertSame(['A2', 'B2', 'C2', 'D2'], array_keys($data['Sheet1'][2]));

        // 3rd row has cells A3, B3
        self::assertSame(['A3', 'B3'], array_keys($data['Sheet1'][3]));

        // the 4th row does not contain consecutive cells
        // it has cells A4 and C4, cell B4 is missing but included because missing cells have been requested
        self::assertSame(['A4', 'B4', 'C4'], array_keys($data['Sheet1'][4]));

        // 5th row is missing but included as an empty row
        self::assertSame([], array_keys($data['Sheet1'][5]));

        // 6th row has a single cell, A6
        self::assertSame(['A6'], array_keys($data['Sheet1'][6]));

        // 7th row has a single cell, A7
        self::assertSame(['A7'], array_keys($data['Sheet1'][7]));

        // the worksheet "Sheet2" has rows 1 and 2
        self::assertSame([1, 2], array_keys($data['Sheet2']));

        // 1st row has cells A1, B1, C1
        self::assertSame(['A1', 'B1', 'C1'], array_keys($data['Sheet2'][1]));

        // 2nd row has a single cell, A1
        self::assertSame(['A2'], array_keys($data['Sheet2'][2]));

        // the worksheet "Table" has rows 1 - 7
        self::assertSame([1, 2, 3, 4, 5, 6, 7], array_keys($data['Table']));
    }

    #[Test]
    function it_reads_as_array_with_column_index()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            ->useColumnIndex()
            ->readAsArray();

        // the workbook contains three worksheets: 'Sheet1', 'Sheet2', 'Table'
        self::assertSame(['Sheet1', 'Sheet2', 'Table'], array_keys($data));

        // the worksheet "Sheet1" has rows 1 - 8 but row 5 is missing
        self::assertSame([1, 2, 3, 4, 6, 7, 8], array_keys($data['Sheet1']));

        // 1st row has cell indexes 1 (A1), 2 (B1), 3 (C1); the cells are indexed by their index values
        self::assertSame([1, 2, 3], array_keys($data['Sheet1'][1]));
        self::assertSame('This is some', $data['Sheet1'][1][1]);
        self::assertSame('very interesting', $data['Sheet1'][1][2]);
        self::assertSame('data to look at.', $data['Sheet1'][1][3]);

        // 2nd row
        // 2nd row has cells 1 (A2), 2 (B2), 3 (C2), 4 (D2)
        self::assertSame([1, 2, 3, 4], array_keys($data['Sheet1'][2]));
        self::assertSame(1, $data['Sheet1'][2][1]);
        self::assertSame(-2, $data['Sheet1'][2][2]);
        self::assertSame(3.14, $data['Sheet1'][2][3]);
        self::assertSame(1, $data['Sheet1'][2][4]);

        // 3rd row has cells 1 (A3), 2 (B3)
        self::assertSame([1, 2], array_keys($data['Sheet1'][3]));
        self::assertEquals(new DateTime('01.04.2026'), $data['Sheet1'][3][1]);
        self::assertEquals(new DateTime('18.03.1974 19:14:23'), $data['Sheet1'][3][2]);

        // the 4th row does not contain consecutive cells, it has cells 1 (A4) and 3 (C4), cell 2 (B4) is missing
        self::assertSame([1, 3], array_keys($data['Sheet1'][4]));
        self::assertSame(2.14, $data['Sheet1'][4][1]);
        self::assertSame('Skipped cell to the left', $data['Sheet1'][4][3]);

        // 6th row has a single cell, 1 (A6)
        self::assertSame([1], array_keys($data['Sheet1'][6]));
        self::assertSame('Skipped row above', $data['Sheet1'][6][1]);

        // 7th row has a single cell, 1 (A7)
        self::assertSame([1], array_keys($data['Sheet1'][7]));
        self::assertSame('W€ love ümläüts, don\'t we?', $data['Sheet1'][7][1]);
    }

    #[Test]
    function it_reads_as_array_with_column_index_and_includes_missing_cells_and_rows()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            ->includeMissingCells()
            ->includeMissingRows()
            ->useColumnIndex()
            ->readAsArray();


        // the workbook contains three worksheets: 'Sheet1', 'Sheet2', 'Table'
        self::assertSame(['Sheet1', 'Sheet2', 'Table'], array_keys($data));

        // the worksheet "Sheet1" has rows 1 - 8, row 5 is missing but included because missing rows have been requested
        self::assertSame([1, 2, 3, 4, 5, 6, 7, 8], array_keys($data['Sheet1']));

        // 1st row has cell indexes 1 (A1), 2 (B1), 3 (C1); the cells are indexed by their index values
        self::assertSame([1, 2, 3], array_keys($data['Sheet1'][1]));
        self::assertSame('This is some', $data['Sheet1'][1][1]);
        self::assertSame('very interesting', $data['Sheet1'][1][2]);
        self::assertSame('data to look at.', $data['Sheet1'][1][3]);

        // 2nd row
        // 2nd row has cells 1 (A2), 2 (B2), 3 (C2), 4 (D2)
        self::assertSame([1, 2, 3, 4], array_keys($data['Sheet1'][2]));
        self::assertSame(1, $data['Sheet1'][2][1]);
        self::assertSame(-2, $data['Sheet1'][2][2]);
        self::assertSame(3.14, $data['Sheet1'][2][3]);
        self::assertSame(1, $data['Sheet1'][2][4]);

        // 3rd row has cells 1 (A3), 2 (B3)
        self::assertSame([1, 2], array_keys($data['Sheet1'][3]));
        self::assertEquals(new DateTime('01.04.2026'), $data['Sheet1'][3][1]);
        self::assertEquals(new DateTime('18.03.1974 19:14:23'), $data['Sheet1'][3][2]);

        // the 4th row does not contain consecutive cells, it has cells 1 (A4) and 3 (C4)
        // cell 2 (B4) is missing but included because missing cells have been requested
        self::assertSame([1, 2, 3], array_keys($data['Sheet1'][4]));
        self::assertSame(2.14, $data['Sheet1'][4][1]);
        self::assertNull($data['Sheet1'][4][2]); // missing cell returns null
        self::assertSame('Skipped cell to the left', $data['Sheet1'][4][3]);

        // 5th row is missing but included as empty because missing rows have been requested
        self::assertSame([], array_keys($data['Sheet1'][5]));

        // 6th row has a single cell, 1 (A6)
        self::assertSame([1], array_keys($data['Sheet1'][6]));
        self::assertSame('Skipped row above', $data['Sheet1'][6][1]);

        // 7th row has a single cell, 1 (A7)
        self::assertSame([1], array_keys($data['Sheet1'][7]));
        self::assertSame('W€ love ümläüts, don\'t we?', $data['Sheet1'][7][1]);
    }
}
