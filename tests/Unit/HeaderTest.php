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
        $reader->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx');

        $data = $reader->worksheets(['Sheet1'])->readWithHeader();

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

        // readWithHeader() keeps track of the header per worksheet
        $headers = $reader->getHeaders();
        self::assertCount(1, $headers);
        self::assertArrayHasKey('Sheet1', $headers);
        self::assertEquals('Column A', $headers['Sheet1'][1]);
        self::assertEquals('Column B', $headers['Sheet1'][2]);
        self::assertEquals('Column C', $headers['Sheet1'][3]);
    }

    #[Test]
    function it_treats_the_third_line_as_headers()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx');

        $data = $reader->worksheets(['Sheet2'])->readWithHeader(3);

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
        $reader->open(self::INPUT_FILES_DIR . 'HeaderRow.xlsx');

        $data = $reader->readWithHeader(['Sheet1' => 1, 'Sheet2' => 3]);

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

        $headers = $reader->getHeaders();
        self::assertCount(2, $headers);
        self::assertArrayHasKey('Sheet1', $headers);
        self::assertEquals('Column A', $headers['Sheet1'][1]);
        self::assertEquals('Column B', $headers['Sheet1'][2]);
        self::assertEquals('Column C', $headers['Sheet1'][3]);
        self::assertArrayHasKey('Sheet2', $headers);
        self::assertEquals('Column A', $headers['Sheet2'][1]);
        self::assertEquals('Column B', $headers['Sheet2'][2]);
        self::assertEquals('Column C', $headers['Sheet2'][3]);
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
}
