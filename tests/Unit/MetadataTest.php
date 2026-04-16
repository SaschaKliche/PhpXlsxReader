<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SaschaKliche\PhpXlsxReader\Model\Metadata;
use SaschaKliche\PhpXlsxReader\Tests\AbstractTestCase;
use SaschaKliche\PhpXlsxReader\XlsxReader;

class MetadataTest extends AbstractTestCase
{
    #[Test]
    function it_provides_access_to_metadata_without_loading_the_worksheets()
    {
        $reader = new XlsxReader();

        $metadata = $reader
            ->open(self::INPUT_FILES_DIR . 'Basic.xlsx')
            ->getMetadata();

        self::assertFalse($reader->worksheetsHaveBeenLoaded());
        self::assertNotEmpty($metadata);
        self::assertTrue($metadata->has(Metadata::CREATED));
        self::assertTrue($metadata->has(Metadata::CREATOR));
        self::assertTrue($metadata->has(Metadata::LAST_MODIFIED_BY));
        self::assertTrue($metadata->has(Metadata::MODIFIED));
        self::assertFalse($metadata->has('invalid-property-name'));
        self::assertEquals('Sascha Kliche', $metadata->get(Metadata::CREATOR));
        // e.g. 2026-03-24T17:17:24Z
        self::assertMatchesRegularExpression('#^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$#', $metadata->get(Metadata::CREATED));
        self::assertMatchesRegularExpression('#^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$#', $metadata->get(Metadata::MODIFIED));
    }
}
