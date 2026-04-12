<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Reader;

use XMLReader;
use ZipArchive;

class AbstractReader
{
    protected const string ZIP_URL = 'zip://';

    public static function zipEntryExists(string $filePath, string $entryPath): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return false;
        }

        $found = $zip->locateName(ltrim($entryPath, '#'));

        $zip->close();

        return $found !== false;
    }

    /**
     * Return either all attributes or only the specified attributes.
     * Returns null for attributes that are not found.
     *
     * @param XMLReader $xml
     * @param string[] $attributeNames
     * @return array
     */
    public function getAttributes(XMLReader $xml, array $attributeNames = []): array
    {
        $attributes = [];

        if ($attributeNames !== []) {
            foreach ($attributeNames as $attributeName) {
                $attributes[$attributeName] = $xml->getAttribute($attributeName);
            }
            return $attributes;
        }

        if (!$xml->hasAttributes) {
            return $attributes;
        }

        while ($xml->moveToNextAttribute()) {
            $attributes[$xml->name] = $xml->value;
        }

        return $attributes;
    }

    /** @noinspection PhpUnused */
    public function getNodeTypeLabel(XMLReader $xml): string
    {
        return match ($xml->nodeType) {
            XMLREADER::ATTRIBUTE => 'attribute',
            XMLREADER::CDATA => 'cdata',
            XMLREADER::COMMENT => 'comment',
            XMLREADER::ELEMENT => 'element',
            XMLREADER::END_ELEMENT => 'end_element',
            XMLREADER::ENTITY => 'entity',
            XMLREADER::TEXT => 'text',
            default => 'unknown (' . $xml->nodeType . ')',
        };
    }

    public function readUntil(XMLReader $xml, string $nodeName, bool $recursive = false): bool
    {
        while (($recursive ? $xml->read() : $xml->next())) {
            if ($xml->name === $nodeName) {
                return true;
            }
        }

        return false;
    }
}
