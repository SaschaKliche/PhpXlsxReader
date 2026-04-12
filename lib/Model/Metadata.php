<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Model;

use SaschaKliche\PhpXlsxReader\Reader\MetadataReader;

class Metadata
{
    public const string CREATED = MetadataReader::CREATED;
    public const string CREATOR = MetadataReader::CREATOR;
    public const string LAST_MODIFIED_BY = MetadataReader::LAST_MODIFIED_BY;
    public const string MODIFIED = MetadataReader::MODIFIED;

    public function __construct(protected array $metadata = [])
    {
    }

    public function has(string $propertyName): bool
    {
        return isset($this->metadata[$propertyName]);
    }

    public function get(string $propertyName): mixed
    {
        return $this->metadata[$propertyName] ?? null;
    }
}
