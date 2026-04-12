<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Reader;

use Exception;
use RuntimeException;
use XMLReader;

class SharedStringsReader extends AbstractReader
{
    protected const string PATH_SHARED_STRINGS = '#xl/sharedStrings.xml';

    protected const string ELEMENT_SHARED_STRINGS = 'sst';
    protected const string ATTRIBUTE_COUNT = 'uniqueCount';

    protected int $numberOfStrings = 0;

    /** @var string[] */
    protected array $sharedStrings = [];

    public static function from(string $fileName): static
    {
        $sharedStrings = new self();
        $sharedStrings->load($fileName);

        return $sharedStrings;
    }

    public function load(string $fileName): void
    {
        if (!self::zipEntryExists($fileName, self::PATH_SHARED_STRINGS)) {
            return;
        }

        $reader = new XMLReader();
        try {
            if ($reader->open(self::ZIP_URL . $fileName . self::PATH_SHARED_STRINGS) === false) {
                return;
            }
        } catch (Exception) {
            // the shared string file doesn't have to exist
            return;
        }

        /*
         * File layout
         * <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
         * <sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="<integer>" uniqueCount="<integer>">
         *  <si><t>The actual text</t></si>
         *  ... more <si><t>...</t></si> entries
         * </sst>
         */
        if (!$this->readUntil($reader, self::ELEMENT_SHARED_STRINGS)) {
            $reader->close();
            throw new RuntimeException('Unable to read shared strings');
        }

        $count = (int) ($this->getAttributes($reader, [self::ATTRIBUTE_COUNT])[self::ATTRIBUTE_COUNT] ?? 0);
        if ($count === 0) {
            $reader->close();
            return;
        }

        while ($reader->read()) {
            if ($reader->nodeType !== XMLREADER::TEXT) {
                continue;
            }

            $this->sharedStrings[] = $reader->value;
            $this->numberOfStrings++;
        }

        $reader->close();

        if ($count !== $this->numberOfStrings) {
            throw new RuntimeException('Expected ' . $count . ' shared strings, got ' . $this->numberOfStrings);
        }
    }

    public function getSharedString(string $id): string
    {
        return $this->sharedStrings[$id];
    }
}
