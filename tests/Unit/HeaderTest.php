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
            ->worksheets(['Sheet1'])
            ->readWithHeader();

        self::assertCount(1, $data); // only one worksheet
        self::assertArrayHasKey('Sheet1', $data);

        // first row containing headers is removed
        self::assertArrayNotHasKey(1, $data['Sheet1']);

        // all other rows are returned
        self::assertArrayHasKey(2, $data['Sheet1']);
        self::assertArrayHasKey(3, $data['Sheet1']);
        self::assertArrayHasKey(4, $data['Sheet1']);
        self::assertArrayHasKey(5, $data['Sheet1']);
        self::assertArrayHasKey(6, $data['Sheet1']);
        self::assertArrayNotHasKey('A2', $data['Sheet1'][2]);
        self::assertArrayHasKey('Column A', $data['Sheet1'][2]);
        self::assertArrayHasKey('Column B', $data['Sheet1'][2]);
        self::assertArrayHasKey('Column C', $data['Sheet1'][2]);
    }

    #[Test]
    function it_treats_the_third_line_as_headers()
    {
        $reader = new XlsxReader();

        $data = $reader
            ->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx')
            ->worksheets(['Sheet2'])
            ->readWithHeader(3);

        self::assertArrayHasKey('Sheet2', $data);

        // third row containing headers is removed
        self::assertArrayNotHasKey(3, $data['Sheet2']);

        // all other rows are returned
        self::assertArrayHasKey(1, $data['Sheet2']);
        self::assertArrayHasKey(2, $data['Sheet2']);
        self::assertArrayHasKey(4, $data['Sheet2']);
        self::assertArrayHasKey(5, $data['Sheet2']);
        self::assertArrayHasKey(6, $data['Sheet2']);
        self::assertArrayNotHasKey('A1', $data['Sheet2'][2]);
        self::assertArrayHasKey('Column A', $data['Sheet2'][2]);
        self::assertArrayHasKey('Column B', $data['Sheet2'][2]);
        self::assertArrayHasKey('Column C', $data['Sheet2'][2]);
    }

    #[Test]
    function it_treats_the_configured_line_as_headers()
    {
        $reader = new XlsxReader();

        $data = $reader
            ->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx')
            ->readWithHeader(['Sheet1' => 1, 'Sheet2' => 3]);

        self::assertArrayHasKey('Sheet1', $data);

        // first row containing headers is removed
        self::assertArrayNotHasKey(1, $data['Sheet1']);

        // all other rows are returned
        self::assertArrayHasKey(2, $data['Sheet1']);
        self::assertArrayHasKey(3, $data['Sheet1']);
        self::assertArrayHasKey(4, $data['Sheet1']);
        self::assertArrayHasKey(5, $data['Sheet1']);
        self::assertArrayHasKey(6, $data['Sheet1']);
        self::assertArrayNotHasKey('A2', $data['Sheet1'][2]);
        self::assertArrayHasKey('Column A', $data['Sheet1'][2]);
        self::assertArrayHasKey('Column B', $data['Sheet1'][2]);
        self::assertArrayHasKey('Column C', $data['Sheet1'][2]);

        self::assertArrayHasKey('Sheet2', $data);

        // third row containing headers is removed
        self::assertArrayNotHasKey(3, $data['Sheet2']);

        // all other rows are returned
        self::assertArrayHasKey(1, $data['Sheet2']);
        self::assertArrayHasKey(2, $data['Sheet2']);
        self::assertArrayHasKey(4, $data['Sheet2']);
        self::assertArrayHasKey(5, $data['Sheet2']);
        self::assertArrayHasKey(6, $data['Sheet2']);
        self::assertArrayNotHasKey('A1', $data['Sheet2'][2]);
        self::assertArrayHasKey('Column A', $data['Sheet2'][2]);
        self::assertArrayHasKey('Column B', $data['Sheet2'][2]);
        self::assertArrayHasKey('Column C', $data['Sheet2'][2]);
    }

    #[Test]
    function with_header_requires_a_header_for_each_column_with_data()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No header exists for column 4 of row 2 on worksheet "Sheet1"');

        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        $reader->readWithHeader();
    }

    #[Test]
    function it_provides_access_to_the_headers_from_all_worksheets()
    {
        $reader = new XlsxReader();

        $reader
            ->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx')
            ->readWithHeader(['Sheet1' => 1, 'Sheet2' => 3]);

        $headers = $reader->getHeaders();

        self::assertEquals(
            [
                'Sheet1' => [1 => 'Column A', 'Column B', 'Column C'],
                'Sheet2' => [1 => 'Column A', 'Column B', 'Column C'],
                'Sheet3' => [1 => 'Column A', 'Column B', 'Column C'],
            ],
            $headers
        );
    }

    #[Test]
    function it_provides_access_to_the_header_from_a_single_worksheet()
    {
        $reader = new XlsxReader();

        $data = $reader
            ->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx')
            ->worksheets(['Sheet3'])
            ->readWithHeader();

        self::assertEquals(
            [
                'Sheet3' => [
                    2 => ['Column B' => -42, 'Column C' => 0.001],
                    ['Column A' => 20, 'Column C' => 1],
                    ['Column A' => 30, 'Column B' => 9.837844, 'Column C' => 2],
                    ['Column A' => 40, 'Column B' => -273.14],
                    ['Column A' => 50, 'Column B' => 99.99, 'Column C' => 8],
                ],
            ],
            $data
        );

        $headersSheet3 = $reader->getHeaders('Sheet3');
        self::assertEquals([1 => 'Column A', 'Column B', 'Column C'], $headersSheet3);
        // A naive array_keys(reset($data['Sheet3'])) wouldn't work to retrieve the columns.
        // That would return a 0-based array containing 'Column B' and 'Column C'
        // because the first data row does not contain all columns.
    }

    #[Test]
    function non_existent_worksheet_throws_an_exception()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No header for worksheet "DoesNotExist"');

        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx');

        $reader->getHeaders('DoesNotExist');
    }

    #[Test]
    function it_adds_missing_columns_with_header_name_if_requested()
    {
        $reader = new XlsxReader();

        $data = $reader
            ->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx')
            ->worksheets(['Sheet3'])
            ->includeMissingCells()
            ->readWithHeader();

        self::assertEquals(
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
        self::assertEquals([1 => 'Column A', 'Column B', 'Column C'], $headersSheet3);
    }
}
