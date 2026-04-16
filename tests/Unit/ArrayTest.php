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
    function it_reads_as_array_with_column_address()
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
        $data = $reader->skipMissingCells()->skipMissingRows()->readAsArray();

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

        // the workbook has a worksheet called "Sheet1"
        self::assertArrayHasKey('Sheet1', $data);
        // the worksheet "sheet1" has a row 1
        self::assertArrayHasKey(1, $data['Sheet1']);
        // the row 1 on worksheet "sheet1" has a cell A1 (first column)
        self::assertArrayHasKey('A1', $data['Sheet1'][1]);
        // the value of A1 is "This is some"
        self::assertEquals('This is some', $data['Sheet1'][1]['A1']); // string content
        self::assertEquals('very interesting', $data['Sheet1'][1]['B1']);
        self::assertEquals('data to look at.', $data['Sheet1'][1]['C1']);
        // 2nd row
        self::assertIsInt($data['Sheet1'][2]['A2']);
        self::assertEquals(1, $data['Sheet1'][2]['A2']); // positive integer
        self::assertEquals(-2, $data['Sheet1'][2]['B2']); // negative integer
        self::assertIsFloat($data['Sheet1'][2]['C2']);
        self::assertEquals(3.14, $data['Sheet1'][2]['C2']); // float
        self::assertIsInt($data['Sheet1'][2]['D2']);
        self::assertEquals(1, $data['Sheet1'][2]['D2']); // 1, displayed as 100% percent in the application
        // 3rd row
        self::assertInstanceOf(DateTime::class, $data['Sheet1'][3]['A3']);
        self::assertEquals(new DateTime('01.04.2026'), $data['Sheet1'][3]['A3']);
        self::assertInstanceOf(DateTime::class, $data['Sheet1'][3]['B3']);
        self::assertEquals(new DateTime('18.03.1974 19:14:23'), $data['Sheet1'][3]['B3']);
        // the 4th row does not contain consecutive cells
        // the cell A4 contains a formula, we return the previously computed value that is stored within the file
        self::assertEquals(2.14, $data['Sheet1'][4]['A4']);
        // cell B4 is missing
        self::assertArrayNotHasKey('B4', $data['Sheet1'][4]);
        self::assertEquals('Skipped cell to the left', $data['Sheet1'][4]['C4']);
        // 5th row is missing
        self::assertArrayNotHasKey(5, $data['Sheet1']);
        // 6th row is present
        self::assertEquals('Skipped row above', $data['Sheet1'][6]['A6']);
        // 7th row contains umlauts and euro sign
        self::assertEquals('W€ love ümläüts, don\'t we?', $data['Sheet1'][7]['A7']);

        // the workbook has another worksheet called "Sheet2"
        // this worksheet also contains fixed rows and columns which doesn't affect reading the values
        self::assertArrayHasKey('Sheet2', $data);
        self::assertEquals('The second sheet', $data['Sheet2'][1]['A1']);
        // the cell A2 contains a hyperlink, we return the display value
        self::assertEquals('Github', $data['Sheet2'][2]['A2']);

        // the workbook has a final worksheet called "Table"
        // this worksheet has a table containing the data which doesn't affect reading the values
        // this worksheet also contains an image which is ignored
        self::assertArrayHasKey('Table', $data);
        self::assertEquals('Column A', $data['Table'][1]['A1']);
        self::assertEquals('Column B', $data['Table'][1]['B1']);
        self::assertEquals('Column C', $data['Table'][1]['C1']);
        self::assertEquals(10, $data['Table'][2]['A2']);
        self::assertEquals(-42, $data['Table'][2]['B2']);
        self::assertEquals(0.001, $data['Table'][2]['C2']);
        self::assertEquals(20, $data['Table'][3]['A3']);
        self::assertEquals(58.12, $data['Table'][3]['B3']);
        self::assertEquals(1, $data['Table'][3]['C3']);
        self::assertEquals(30, $data['Table'][4]['A4']);
        self::assertEquals(9.837844, $data['Table'][4]['B4']);
        self::assertEquals(2, $data['Table'][4]['C4']);
        self::assertEquals(40, $data['Table'][5]['A5']);
        self::assertEquals(-273.14, $data['Table'][5]['B5']);
        self::assertEquals(4, $data['Table'][5]['C5']);
        self::assertEquals(50, $data['Table'][6]['A6']);
        self::assertEquals(99.99, $data['Table'][6]['B6']);
        self::assertEquals(8, $data['Table'][6]['C6']);
        // the last row is a formula row in the table, the calculated values are returned like for other cells containing a formula
        self::assertEquals(30, $data['Table'][7]['A7']);
        self::assertEquals(-147.192156, $data['Table'][7]['B7']);
        self::assertEquals(15.001, $data['Table'][7]['C7']);

        // we didn't use readWithHeader() so we should not have any headers
        self::assertEmpty($reader->getHeaders());
    }

    #[Test]
    function it_reads_as_array_with_column_address_and_include_missing_cells_and_rows()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader->includeMissingCells()->includeMissingRows()->readAsArray();

        // the workbook has a worksheet called "sheet1"
        self::assertArrayHasKey('Sheet1', $data);
        // the worksheet "sheet1" has a row 1
        self::assertArrayHasKey(1, $data['Sheet1']);
        // the row 1 on worksheet "sheet1" has a cell A1 (first column)
        self::assertArrayHasKey('A1', $data['Sheet1'][1]);
        // the value of A1 is "This is some"
        self::assertEquals('This is some', $data['Sheet1'][1]['A1']); // string content
        self::assertEquals('very interesting', $data['Sheet1'][1]['B1']);
        self::assertEquals('data to look at.', $data['Sheet1'][1]['C1']);
        // 2nd row
        self::assertEquals(1, $data['Sheet1'][2]['A2']); // positive integer
        self::assertEquals(-2, $data['Sheet1'][2]['B2']); // negative integer
        self::assertEquals(3.14, $data['Sheet1'][2]['C2']); // float
        self::assertEquals(1, $data['Sheet1'][2]['D2']); // 1, displayed as 100% percent in the application
        // 3rd row
        self::assertEquals(new DateTime('01.04.2026'), $data['Sheet1'][3]['A3']);
        self::assertEquals(new DateTime('18.03.1974 19:14:23'), $data['Sheet1'][3]['B3']);
        // the 4th row does not contain consecutive cells
        // the cell A4 contains a formula, we return the previously computed value that is stored within the file
        self::assertEquals(2.14, $data['Sheet1'][4]['A4']);
        // cell B4 is missing but included as null
        self::assertArrayHasKey('B4', $data['Sheet1'][4]);
        self::assertNull($data['Sheet1'][4]['B4']);
        self::assertEquals('Skipped cell to the left', $data['Sheet1'][4]['C4']);
        // 5th row is missing
        self::assertArrayHasKey(5, $data['Sheet1']);
        self::assertEquals([], $data['Sheet1'][5]);
        // 6th row is present
        self::assertEquals('Skipped row above', $data['Sheet1'][6]['A6']);
        // 7th row contains umlauts and euro sign
        self::assertEquals('W€ love ümläüts, don\'t we?', $data['Sheet1'][7]['A7']);

        // workbook has another worksheet called "Sheet2"
        // this worksheet also contains fixed rows and columns which doesn't affect reading the values
        self::assertArrayHasKey('Sheet2', $data);
        self::assertEquals('The second sheet', $data['Sheet2'][1]['A1']);
        // the cell A2 contains a hyperlink, we return the display value
        self::assertEquals('Github', $data['Sheet2'][2]['A2']);

        // workbook has a final worksheet called "Table"
        // this worksheet has a table containing the data which doesn't affect reading the values
        // this worksheet also contains an image which is ignored
        self::assertArrayHasKey('Table', $data);
        self::assertEquals('Column A', $data['Table'][1]['A1']);
        self::assertEquals('Column B', $data['Table'][1]['B1']);
        self::assertEquals('Column C', $data['Table'][1]['C1']);
        self::assertEquals(10, $data['Table'][2]['A2']);
        self::assertEquals(-42, $data['Table'][2]['B2']);
        self::assertEquals(0.001, $data['Table'][2]['C2']);
        self::assertEquals(20, $data['Table'][3]['A3']);
        self::assertEquals(58.12, $data['Table'][3]['B3']);
        self::assertEquals(1, $data['Table'][3]['C3']);
        self::assertEquals(30, $data['Table'][4]['A4']);
        self::assertEquals(9.837844, $data['Table'][4]['B4']);
        self::assertEquals(2, $data['Table'][4]['C4']);
        self::assertEquals(40, $data['Table'][5]['A5']);
        self::assertEquals(-273.14, $data['Table'][5]['B5']);
        self::assertEquals(4, $data['Table'][5]['C5']);
        self::assertEquals(50, $data['Table'][6]['A6']);
        self::assertEquals(99.99, $data['Table'][6]['B6']);
        self::assertEquals(8, $data['Table'][6]['C6']);
        // the last row is a formula row in the table, the calculated values are returned like for other cells containing a formula
        self::assertEquals(30, $data['Table'][7]['A7']);
        self::assertEquals(-147.192156, $data['Table'][7]['B7']);
        self::assertEquals(15.001, $data['Table'][7]['C7']);
    }

    #[Test]
    function it_reads_as_array_with_column_index()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader->useColumnIndex()->readAsArray();

        // the workbook has a worksheet called "sheet1"
        self::assertArrayHasKey('Sheet1', $data);
        // the worksheet "sheet1" has a row 1
        self::assertArrayHasKey('1', $data['Sheet1']);
        // the first row has a column 1 (=A)
        self::assertArrayHasKey(1, $data['Sheet1'][1]);
        self::assertEquals('This is some', $data['Sheet1'][1][1]);
        self::assertEquals('very interesting', $data['Sheet1'][1][2]);
        self::assertEquals('data to look at.', $data['Sheet1'][1][3]);
        // 2nd row
        self::assertEquals(1, $data['Sheet1'][2][1]);
        self::assertEquals(-2, $data['Sheet1'][2][2]);
        self::assertEquals(3.14, $data['Sheet1'][2][3]);
        self::assertEquals(1, $data['Sheet1'][2][4]);
        // 3rd row
        self::assertEquals(new DateTime('01.04.2026'), $data['Sheet1'][3][1]);
        self::assertEquals(new DateTime('18.03.1974 19:14:23'), $data['Sheet1'][3][2]);
        // the 4th row does not contain consecutive cells
        // the cell A4 contains a formula, we return the previously computed value that is stored within the file
        self::assertEquals(2.14, $data['Sheet1'][4][1]);
        // cell B4 is missing
        self::assertArrayNotHasKey(2, $data['Sheet1'][4]);
        self::assertEquals('Skipped cell to the left', $data['Sheet1'][4][3]);
        // 5th row is missing
        self::assertArrayNotHasKey(5, $data['Sheet1']);
        // 6th row is present
        self::assertEquals('Skipped row above', $data['Sheet1'][6][1]);
        // 7th row contains umlauts and euro sign
        self::assertEquals('W€ love ümläüts, don\'t we?', $data['Sheet1'][7][1]);
    }

    #[Test]
    function it_reads_as_array_with_column_index_and_include_missing_cells_and_rows()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $data = $reader
            ->includeMissingCells()
            ->includeMissingRows()
            ->useColumnIndex()
            ->readAsArray();

        // the workbook has a worksheet called "sheet1"
        self::assertArrayHasKey('Sheet1', $data);
        // the worksheet "sheet1" has a row 1
        self::assertArrayHasKey('1', $data['Sheet1']);
        // the first row has a column 1 (=A)
        self::assertArrayHasKey(1, $data['Sheet1'][1]);
        self::assertEquals('This is some', $data['Sheet1'][1][1]);
        self::assertEquals('very interesting', $data['Sheet1'][1][2]);
        self::assertEquals('data to look at.', $data['Sheet1'][1][3]);
        // 2nd row
        self::assertEquals(1, $data['Sheet1'][2][1]);
        self::assertEquals(-2, $data['Sheet1'][2][2]);
        self::assertEquals(3.14, $data['Sheet1'][2][3]);
        self::assertEquals(1, $data['Sheet1'][2][4]);
        // 3rd row
        self::assertEquals(new DateTime('01.04.2026'), $data['Sheet1'][3][1]);
        self::assertEquals(new DateTime('18.03.1974 19:14:23'), $data['Sheet1'][3][2]);
        // the 4th row does not contain consecutive cells
        // the cell A4 contains a formula, we return the previously computed value that is stored within the file
        self::assertEquals(2.14, $data['Sheet1'][4][1]);
        // cell B4 is missing but included as null
        self::assertArrayHasKey(2, $data['Sheet1'][4]);
        self::assertNull($data['Sheet1'][4][2]);
        self::assertEquals('Skipped cell to the left', $data['Sheet1'][4][3]);
        // 5th row is missing
        self::assertArrayHasKey(5, $data['Sheet1']);
        self::assertEquals([], $data['Sheet1'][5]);
        // 6th row is present
        self::assertEquals('Skipped row above', $data['Sheet1'][6][1]);
        // 7th row contains umlauts and euro sign
        self::assertEquals('W€ love ümläüts, don\'t we?', $data['Sheet1'][7][1]);
    }
}
