<?php /** @noinspection HttpUrlsUsage */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Reader;

use RuntimeException;
use SaschaKliche\PhpXlsxReader\Model\Metadata;
use XMLReader;

class MetadataReader extends AbstractReader
{
    public const string CREATED = 'created';
    public const string CREATOR = 'creator';
    public const string LAST_MODIFIED_BY = 'lastModifiedBy';
    public const string MODIFIED = 'modified';

    protected const string PATH_METADATA = '#docProps/core.xml';

    protected const string NAMESPACE_CP = 'cp:';
    protected const string NAMESPACE_DC = 'dc:';
    protected const string NAMESPACE_DCTERMS = 'dcterms:';

    public static function from(string $fileName): ?Metadata
    {
        return (new self())->load($fileName);
    }

    protected function load(string $fileName): Metadata
    {
        if (!self::zipEntryExists($fileName, self::PATH_METADATA)) {
            return new Metadata();
        }

        $metadata = [];

        $fileName = self::ZIP_URL . $fileName . self::PATH_METADATA;
        $reader = new XMLReader();
        if ($reader->open($fileName) === false) {
            throw new RuntimeException('Unable to open file: ' . $fileName);
        }

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }

            switch ($reader->name) {
                case self::NAMESPACE_CP . self::LAST_MODIFIED_BY:
                    $metadata[self::LAST_MODIFIED_BY] = $reader->readString();
                    break;
                case self::NAMESPACE_DC . self::CREATOR:
                    $metadata[self::CREATOR] = $reader->readString();
                    break;
                case self::NAMESPACE_DCTERMS . self::CREATED:
                    $metadata[self::CREATED] = $reader->readString();
                    break;
                case self::NAMESPACE_DCTERMS . self::MODIFIED:
                    $metadata[self::MODIFIED] = $reader->readString();
                    break;
                default:
                    break;
            }
        }

        $reader->close();

        return new Metadata($metadata);
    }
}
