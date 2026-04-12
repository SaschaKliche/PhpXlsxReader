<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SaschaKliche\PhpXlsxReader\Configuration;

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
        self::assertEquals([], $configuration->get(Configuration::WORKSHEETS_TO_LOAD));
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

        self::assertEquals($customDefault, $configuration->get('does-not-exist', $customDefault));
    }

    #[Test]
    function a_configuration_value_can_be_changed()
    {
        $configuration = new Configuration;
        self::assertTrue($configuration->get(Configuration::SKIP_MISSING_CELLS));

        $configuration->set(Configuration::SKIP_MISSING_CELLS, false);

        self::assertFalse($configuration->get(Configuration::SKIP_MISSING_CELLS));
    }
}
