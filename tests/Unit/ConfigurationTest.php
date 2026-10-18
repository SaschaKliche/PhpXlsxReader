<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SaschaKliche\PhpXlsxReader\Configuration;
use SaschaKliche\PhpXlsxReader\XlsxReader;

class ConfigurationTest extends TestCase
{
    #[Test]
    function it_creates_a_configuration_from_an_array()
    {
        $configurationMap = [
            Configuration::SKIP_MISSING_CELLS => true,
            Configuration::SKIP_MISSING_ROWS => true,
            Configuration::USE_CELL_ADDRESS => true,
            Configuration::WORKSHEETS_TO_LOAD => [],
        ];

        $configuration = Configuration::from($configurationMap);

        self::assertTrue($configuration->get(Configuration::SKIP_MISSING_CELLS));
        self::assertTrue($configuration->get(Configuration::SKIP_MISSING_ROWS));
        self::assertTrue($configuration->get(Configuration::USE_CELL_ADDRESS));
        self::assertSame([], $configuration->get(Configuration::WORKSHEETS_TO_LOAD));
    }

    #[Test]
    function a_non_existing_configuration_value_returns_null_by_default()
    {
        $configuration = new Configuration;

        self::assertNull($configuration->get('does-not-exist'));
    }

    #[Test]
    function a_non_existing_configuration_value_can_return_a_custom_default()
    {
        $customDefault = 'my-default';
        $configuration = new Configuration;

        self::assertSame($customDefault, $configuration->get('does-not-exist', $customDefault));
    }

    #[Test]
    function a_configuration_value_can_be_changed()
    {
        $configuration = new Configuration;
        self::assertTrue($configuration->get(Configuration::SKIP_MISSING_CELLS));

        $configuration->set(Configuration::SKIP_MISSING_CELLS, false);

        self::assertFalse($configuration->get(Configuration::SKIP_MISSING_CELLS));
    }

    #[Test]
    function it_returns_the_default_configuration()
    {
        $default = [
            Configuration::CUSTOM_FORMATS => [],
            Configuration::SKIP_MISSING_CELLS => true,
            Configuration::SKIP_MISSING_ROWS => true,
            Configuration::RETURN_CELL_OBJECTS => false,
            Configuration::READ_FORMULAS => false,
            Configuration::READ_HYPERLINKS => false,
            Configuration::USE_CELL_ADDRESS => true,
            Configuration::USE_DATE_SYSTEM_1900 => true,
            Configuration::COLUMNS_TO_LOAD => [],
            Configuration::ROWS_TO_LOAD => [],
            Configuration::WORKSHEETS_TO_LOAD => [],
        ];

        $configuration = new Configuration;

        self::assertSame($default, $configuration->get());
    }

    #[Test]
    function xlsxreader_accepts_configuration_array_to_overwrite_defaults()
    {
        $expected = [
            Configuration::CUSTOM_FORMATS => [],
            Configuration::SKIP_MISSING_CELLS => false,
            Configuration::SKIP_MISSING_ROWS => false,
            Configuration::RETURN_CELL_OBJECTS => false,
            Configuration::READ_FORMULAS => false,
            Configuration::READ_HYPERLINKS => false,
            Configuration::USE_CELL_ADDRESS => false,
            Configuration::USE_DATE_SYSTEM_1900 => true,
            Configuration::COLUMNS_TO_LOAD => [],
            Configuration::ROWS_TO_LOAD => [],
            Configuration::WORKSHEETS_TO_LOAD => ['Sheet1'],
        ];

        $configurationMap = [
            Configuration::SKIP_MISSING_CELLS => false,
            Configuration::SKIP_MISSING_ROWS => false,
            Configuration::USE_CELL_ADDRESS => false,
            Configuration::WORKSHEETS_TO_LOAD => ['Sheet1'],
        ];

        $reader = new XlsxReader($configurationMap);

        self::assertSame($expected, $reader->getConfiguration()->get());
    }

    #[Test]
    function xlsxreader_configuration_helpers_change_default_configuration()
    {
        $expected = [
            Configuration::CUSTOM_FORMATS => [],
            Configuration::SKIP_MISSING_CELLS => true,
            Configuration::SKIP_MISSING_ROWS => true,
            Configuration::RETURN_CELL_OBJECTS => true,
            Configuration::READ_FORMULAS => true,
            Configuration::READ_HYPERLINKS => false,
            Configuration::USE_CELL_ADDRESS => false,
            Configuration::USE_DATE_SYSTEM_1900 => true,
            Configuration::COLUMNS_TO_LOAD => [],
            Configuration::ROWS_TO_LOAD => [],
            Configuration::WORKSHEETS_TO_LOAD => ['Sheet1', 'Sheet2'],
        ];

        $reader = new XlsxReader();
        $reader
            ->useColumnIndex()
            ->returnCellObjects(true)
            ->worksheets(['Sheet1', 'Sheet2']);

        self::assertSame($expected, $reader->getConfiguration()->get());
    }
}
