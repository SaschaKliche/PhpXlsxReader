<?php /** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use SaschaKliche\PhpXlsxReader\Configuration;
use SaschaKliche\PhpXlsxReader\Reader\WorksheetReader;
use SaschaKliche\PhpXlsxReader\Tests\AbstractTestCase;
use SaschaKliche\PhpXlsxReader\XlsxReader;

class WorksheetReaderTest extends AbstractTestCase
{
    #[Test]
    function an_invalid_min_max_configuration_throws_an_exception()
    {
        $reader = new XlsxReader();
        $reader
            ->open(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->rows([Configuration::MIN => 5, Configuration::MAX => 3]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(WorksheetReader::EXCEPTION_MIN_MAX);

        $reader->readAsArray();
    }

    static function provideShouldLoadData(): Generator
    {
        yield 'empty configuration includes request' => [
            [],
            1,
            true,
        ];

        yield 'single item configuration includes request index' => [
            [1 => 0],
            1,
            true,
        ];

        yield 'single item configuration excludes request index' => [
            [1 => 0],
            4,
            false,
        ];

        yield 'simple configuration includes request index' => [
            [1 => 0, 2 => 1, 5 => 2],
            5,
            true,
        ];

        yield 'simple configuration excludes request index' => [
            [1 => 0, 2 => 1, 5 => 2],
            9,
            false,
        ];

        yield 'min configuration includes request index' => [
            [Configuration::MIN => 3],
            5,
            true,
        ];

        yield 'min configuration excludes request index below min' => [
            [Configuration::MIN => 3],
            1,
            false,
        ];

        yield 'max configuration includes request index' => [
            [Configuration::MAX => 3],
            1,
            true,
        ];

        yield 'max configuration excludes request index above max' => [
            [Configuration::MAX => 3],
            5,
            false,
        ];

        yield 'min/max configuration includes request index' => [
            [Configuration::MIN => 3, Configuration::MAX => 6],
            5,
            true,
        ];

        yield 'min/max configuration excludes request index below min' => [
            [Configuration::MIN => 3, Configuration::MAX => 6],
            1,
            false,
        ];

        yield 'min/max configuration excludes request index above max' => [
            [Configuration::MIN => 5, Configuration::MAX => 3],
            9,
            false,
        ];
    }

    #[Test]
    #[DataProvider('provideShouldLoadData')]
    function it_determines_if_a_column_or_row_should_be_loaded(array $configuration, int $index, bool $expected)
    {
        self::assertEquals($expected, WorksheetReader::shouldLoadRowOrColumn($configuration, $index));
    }
}
