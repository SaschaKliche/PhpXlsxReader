<?php /** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use SaschaKliche\PhpXlsxReader\Tests\AbstractTestCase;
use SaschaKliche\PhpXlsxReader\XlsxReader;

class HeaderTest extends AbstractTestCase
{
    #[Test]
    function it_treats_the_first_line_as_headers()
    {
        $reader = new XlsxReader();

        $data = $reader
            ->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx')
            ->worksheet('Sheet1')
            ->readWithHeader();

        // the column header values can be retrieved per worksheet
        $headers = $reader->getHeaders('Sheet1');
        self::assertSame([1 => 'Column A', 'Column B', 'Column C'], $headers);

        // only a single worksheet has been retrieved
        self::assertSame(['Sheet1'], array_keys($data));

        // first row containing headers has been removed, other rows are returned
        self::assertSame([2, 3, 4, 5, 6], array_keys($data['Sheet1']));

        $headers = array_values($headers); // reset to 0-based
        // all rows are indexed by the actual column header values instead of index numbers or characters
        // values are accessed using the column header value
        self::assertSame($headers, array_keys($data['Sheet1'][2]));
        self::assertSame(10, $data['Sheet1'][2]['Column A']);
        self::assertSame(-42, $data['Sheet1'][2]['Column B']);
        self::assertSame(0.001, $data['Sheet1'][2]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet1'][3]));
        self::assertSame(20, $data['Sheet1'][3]['Column A']);
        self::assertSame(58.12, $data['Sheet1'][3]['Column B']);
        self::assertSame(1, $data['Sheet1'][3]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet1'][4]));
        self::assertSame(30, $data['Sheet1'][4]['Column A']);
        self::assertSame(9.837844, $data['Sheet1'][4]['Column B']);
        self::assertSame(2, $data['Sheet1'][4]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet1'][5]));
        self::assertSame(40, $data['Sheet1'][5]['Column A']);
        self::assertSame(-273.14, $data['Sheet1'][5]['Column B']);
        self::assertSame(4, $data['Sheet1'][5]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet1'][6]));
        self::assertSame(50, $data['Sheet1'][6]['Column A']);
        self::assertSame(99.99, $data['Sheet1'][6]['Column B']);
        self::assertSame(8, $data['Sheet1'][6]['Column C']);
    }

    #[Test]
    function it_treats_the_third_line_as_headers()
    {
        $reader = new XlsxReader();

        $data = $reader
            ->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx')
            ->worksheet('Sheet2')
            ->readWithHeader(3);

        $headers = $reader->getHeaders('Sheet2');
        self::assertSame([1 => 'Column A', 'Column B', 'Column C'], $headers);

        // only a single worksheet has been retrieved
        self::assertSame(['Sheet2'], array_keys($data));

        // 3rd row containing headers has been removed, other rows are returned
        self::assertSame([1, 2, 4, 5, 6], array_keys($data['Sheet2']));

        $headers = array_values($headers); // reset to 0-based

        // 1st row contains data
        self::assertSame($headers, array_keys($data['Sheet2'][1]));
        self::assertSame(10, $data['Sheet2'][1]['Column A']);
        self::assertSame(-42, $data['Sheet2'][1]['Column B']);
        self::assertSame(0.001, $data['Sheet2'][1]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet2'][2]));
        self::assertSame(20, $data['Sheet2'][2]['Column A']);
        self::assertSame(58.12, $data['Sheet2'][2]['Column B']);
        self::assertSame(1, $data['Sheet2'][2]['Column C']);

        // 3rd row contains headers, so it's missing

        self::assertSame($headers, array_keys($data['Sheet2'][4]));
        self::assertSame(30, $data['Sheet2'][4]['Column A']);
        self::assertSame(9.837844, $data['Sheet2'][4]['Column B']);
        self::assertSame(2, $data['Sheet2'][4]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet2'][5]));
        self::assertSame(40, $data['Sheet2'][5]['Column A']);
        self::assertSame(-273.14, $data['Sheet2'][5]['Column B']);
        self::assertSame(4, $data['Sheet2'][5]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet2'][6]));
        self::assertSame(50, $data['Sheet2'][6]['Column A']);
        self::assertSame(99.99, $data['Sheet2'][6]['Column B']);
        self::assertSame(8, $data['Sheet2'][6]['Column C']);
    }

    #[Test]
    function it_treats_the_configured_lines_as_headers_per_worksheet()
    {
        $reader = new XlsxReader();

        $data = $reader
            ->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx')
            // explicitly configure header rows for 1st and second sheet
            // other sheets (i.e. 3rd sheet) have 1st row as header row implicitly
            ->readWithHeader(['Sheet1' => 1, 'Sheet2' => 3]);

        // all sheets have the same header values even though they are from different rows
        $headers = $reader->getHeaders('Sheet1');
        self::assertSame([1 => 'Column A', 'Column B', 'Column C'], $headers);
        $headers = $reader->getHeaders('Sheet2');
        self::assertSame([1 => 'Column A', 'Column B', 'Column C'], $headers);
        $headers = $reader->getHeaders('Sheet3');
        self::assertSame([1 => 'Column A', 'Column B', 'Column C'], $headers);

        // all worksheets have been retrieved
        self::assertSame(['Sheet1', 'Sheet2', 'Sheet3'], array_keys($data));

        // first row containing headers has been explicitly removed from Sheet1, other rows are returned
        self::assertSame([2, 3, 4, 5, 6], array_keys($data['Sheet1']));
        // 3rd row containing headers has been explicitly removed from Sheet2, other rows are returned
        self::assertSame([1, 2, 4, 5, 6], array_keys($data['Sheet2']));
        // first row containing headers has been implicitly removed from Sheet3, other rows are returned
        self::assertSame([2, 3, 4, 5, 6], array_keys($data['Sheet3']));

        $headers = array_values($headers); // reset to 0-based

        // Sheet1
        self::assertSame($headers, array_keys($data['Sheet1'][2]));
        self::assertSame(10, $data['Sheet1'][2]['Column A']);
        self::assertSame(-42, $data['Sheet1'][2]['Column B']);
        self::assertSame(0.001, $data['Sheet1'][2]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet1'][3]));
        self::assertSame(20, $data['Sheet1'][3]['Column A']);
        self::assertSame(58.12, $data['Sheet1'][3]['Column B']);
        self::assertSame(1, $data['Sheet1'][3]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet1'][4]));
        self::assertSame(30, $data['Sheet1'][4]['Column A']);
        self::assertSame(9.837844, $data['Sheet1'][4]['Column B']);
        self::assertSame(2, $data['Sheet1'][4]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet1'][5]));
        self::assertSame(40, $data['Sheet1'][5]['Column A']);
        self::assertSame(-273.14, $data['Sheet1'][5]['Column B']);
        self::assertSame(4, $data['Sheet1'][5]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet1'][6]));
        self::assertSame(50, $data['Sheet1'][6]['Column A']);
        self::assertSame(99.99, $data['Sheet1'][6]['Column B']);
        self::assertSame(8, $data['Sheet1'][6]['Column C']);

        // Sheet2
        // 1st row contains data
        self::assertSame($headers, array_keys($data['Sheet2'][1]));
        self::assertSame(10, $data['Sheet2'][1]['Column A']);
        self::assertSame(-42, $data['Sheet2'][1]['Column B']);
        self::assertSame(0.001, $data['Sheet2'][1]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet2'][2]));
        self::assertSame(20, $data['Sheet2'][2]['Column A']);
        self::assertSame(58.12, $data['Sheet2'][2]['Column B']);
        self::assertSame(1, $data['Sheet2'][2]['Column C']);

        // 3rd row contains headers, so it's missing

        self::assertSame($headers, array_keys($data['Sheet2'][4]));
        self::assertSame(30, $data['Sheet2'][4]['Column A']);
        self::assertSame(9.837844, $data['Sheet2'][4]['Column B']);
        self::assertSame(2, $data['Sheet2'][4]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet2'][5]));
        self::assertSame(40, $data['Sheet2'][5]['Column A']);
        self::assertSame(-273.14, $data['Sheet2'][5]['Column B']);
        self::assertSame(4, $data['Sheet2'][5]['Column C']);

        self::assertSame($headers, array_keys($data['Sheet2'][6]));
        self::assertSame(50, $data['Sheet2'][6]['Column A']);
        self::assertSame(99.99, $data['Sheet2'][6]['Column B']);
        self::assertSame(8, $data['Sheet2'][6]['Column C']);

        // Sheet3
        // 2nd row has data in B and C only
        self::assertSame(['Column B', 'Column C'], array_keys($data['Sheet3'][2]));
        self::assertSame(-42, $data['Sheet3'][2]['Column B']);
        self::assertSame(0.001, $data['Sheet3'][2]['Column C']);

        // 3rd row has data in A and C only
        self::assertSame(['Column A', 'Column C'], array_keys($data['Sheet3'][3]));
        self::assertSame(20, $data['Sheet3'][3]['Column A']);
        self::assertSame(1, $data['Sheet3'][3]['Column C']);

        // 4th row has all columns
        self::assertSame($headers, array_keys($data['Sheet3'][4]));
        self::assertSame(30, $data['Sheet3'][4]['Column A']);
        self::assertSame(9.837844, $data['Sheet3'][4]['Column B']);
        self::assertSame(2, $data['Sheet3'][4]['Column C']);

        // 5th row has data in A and B only
        self::assertSame(['Column A', 'Column B'], array_keys($data['Sheet3'][5]));
        self::assertSame(40, $data['Sheet3'][5]['Column A']);
        self::assertSame(-273.14, $data['Sheet3'][5]['Column B']);

        // 6th row has all columns
        self::assertSame($headers, array_keys($data['Sheet3'][6]));
        self::assertSame(50, $data['Sheet3'][6]['Column A']);
        self::assertSame(99.99, $data['Sheet3'][6]['Column B']);
        self::assertSame(8, $data['Sheet3'][6]['Column C']);
    }

    #[Test]
    function with_header_requires_a_header_for_each_column_with_data()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No header exists for column 4 of row 2 on worksheet "Sheet1"');

        $reader->readWithHeader();
    }

    #[Test]
    function it_provides_access_to_the_headers_from_all_worksheets()
    {
        $reader = new XlsxReader();
        $reader
            ->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx')
            ->readWithHeader(['Sheet1' => 1, 'Sheet2' => 3]);

        // column header values can be retrieved for all worksheets at once
        $headers = $reader->getHeaders();

        self::assertSame(
            [
                'Sheet1' => [1 => 'Column A', 'Column B', 'Column C'],
                'Sheet2' => [1 => 'Column A', 'Column B', 'Column C'],
                'Sheet3' => [1 => 'Column A', 'Column B', 'Column C'],
            ],
            $headers
        );
    }

    #[Test]
    function it_provides_access_to_the_header_from_a_single_worksheet_by_name()
    {
        $reader = new XlsxReader();
        $reader
            ->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx')
            ->worksheet('Sheet3')
            ->readWithHeader();

        // retrieving the header values by worksheet name
        $headersSheet3 = $reader->getHeaders('Sheet3');

        self::assertSame([1 => 'Column A', 'Column B', 'Column C'], $headersSheet3);
        // A naive array_keys(reset($data['Sheet3'])) wouldn't work to retrieve the columns.
        // That would return a 0-based array containing 'Column B' and 'Column C'
        // because the first data row does not contain all columns.
    }

    #[Test]
    function it_provides_access_to_the_header_from_a_single_worksheet_by_index()
    {
        $reader = new XlsxReader();
        $reader
            ->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx')
            ->worksheet('Sheet3')
            ->readWithHeader();

        // retrieving the header values by worksheet index number
        $headersSheet3 = $reader->getHeaders(3);

        self::assertSame([1 => 'Column A', 'Column B', 'Column C'], $headersSheet3);
    }

    #[Test]
    function non_existent_worksheet_throws_an_exception()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No header for worksheet "DoesNotExist"');

        $reader->getHeaders('DoesNotExist');
    }

    #[Test]
    function it_adds_missing_columns_with_header_name_if_requested()
    {
        $reader = new XlsxReader();

        $data = $reader
            ->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx')
            ->worksheet('Sheet3')
            ->includeMissingCells()
            ->readWithHeader();

        self::assertSame(
            [
                'Sheet3' => [
                    2 => ['Column A' => null, 'Column B' => -42, 'Column C' => 0.001],
                    ['Column A' => 20, 'Column B' => null, 'Column C' => 1],
                    ['Column A' => 30, 'Column B' => 9.837844, 'Column C' => 2],
                    ['Column A' => 40, 'Column B' => -273.14, 'Column C' => null],
                    ['Column A' => 50, 'Column B' => 99.99, 'Column C' => 8],
                ],
            ],
            $data
        );

        $headersSheet3 = $reader->getHeaders('Sheet3');
        self::assertSame([1 => 'Column A', 'Column B', 'Column C'], $headersSheet3);
    }
}
