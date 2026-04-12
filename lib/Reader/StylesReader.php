<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Reader;

use RuntimeException;
use SaschaKliche\PhpXlsxReader\Model\Styles;
use XMLReader;

class StylesReader extends AbstractReader
{
    protected const string PATH_STYLES = '#xl/styles.xml';

    protected const string INDEX_NUMBER_FORMAT_IDS = 'numberFormatIds';
    protected const string INDEX_NUMBER_FORMATS = 'numberFormats';

    protected const string CELL_FORMATS = 'cellXfs'; // 18.8.10 cellXfs (Cell Formats)
    protected const string CELL_FORMAT = 'xf'; // 18.8.45 xf (Format)

    protected const string NUMBER_FORMATS = 'numFmts'; // 18.8.31 numFmts (Number Formats)
    protected const string NUMBER_FORMAT = 'numFmt'; // 18.8.30 numFmt (Number Format)
    protected const string NUMBER_FORMAT_ATTRIBUTE_FORMAT_CODE = 'formatCode';
    protected const string NUMBER_FORMAT_ID = 'numFmtId';

    public static function from(string $fileName): Styles
    {
        return (new self())->load($fileName);
    }

    protected function load(string $fileName): Styles
    {
        if (!self::zipEntryExists($fileName, self::PATH_STYLES)) {
            throw new RuntimeException('File "' . $fileName . '" does not exist.');
        }

        $reader = new XMLReader();
        if ($reader->open(self::ZIP_URL . $fileName . self::PATH_STYLES) === false) {
            return new Styles([self::INDEX_NUMBER_FORMATS => [], self::INDEX_NUMBER_FORMAT_IDS => []]);
        }

        /*
         * @var array<string, array<int, string>>
         *
         * Index is ...
         * - INDEX_NUMBER_FORMATS contains an mapping of numFmtId to number format string
         * - INDEX_NUMBER_FORMAT_IDS contains a list of numFmtIds
         * - INDEX_CELL_FORMATS contains a list of cell formats (e.g. cell alignment, font, then number format used)
         */
        $styles = [];

        $readingCellFormats = false;
        $readingNumberFormats = false;
        while ($reader->read()) {
            if ($reader->name === self::NUMBER_FORMATS) {
                if ($reader->nodeType === XMLReader::ELEMENT) {
                    $readingNumberFormats = true;
                }
                if ($reader->nodeType === XMLReader::END_ELEMENT) {
                    $readingNumberFormats = false;
                }
                continue;
            }

            if ($readingNumberFormats && $reader->nodeType === XMLReader::ELEMENT && $reader->name === self::NUMBER_FORMAT) {
                $attributes = $this->getAttributes($reader, [self::NUMBER_FORMAT_ATTRIBUTE_FORMAT_CODE, self::NUMBER_FORMAT_ID]);
                if (isset($attributes[self::NUMBER_FORMAT_ID], $attributes[self::NUMBER_FORMAT_ATTRIBUTE_FORMAT_CODE])) {
                    $styles[self::INDEX_NUMBER_FORMATS][$attributes[self::NUMBER_FORMAT_ID]] =
                        $attributes[self::NUMBER_FORMAT_ATTRIBUTE_FORMAT_CODE];
                }
                continue;
            }

            if ($reader->name === self::CELL_FORMATS) {
                if ($reader->nodeType === XMLReader::ELEMENT) {
                    $readingCellFormats = true;
                }
                if ($reader->nodeType === XMLReader::END_ELEMENT) {
                    $readingCellFormats = false;
                }
                continue;
            }

            if ($readingCellFormats && $reader->nodeType === XMLReader::ELEMENT && $reader->name === self::CELL_FORMAT) {
                $attributes = $this->getAttributes($reader, [self::NUMBER_FORMAT_ID]);
                if (isset($attributes[self::NUMBER_FORMAT_ID])) {
                    $styles[self::INDEX_NUMBER_FORMAT_IDS][] = $attributes[self::NUMBER_FORMAT_ID];
                }
            }
        }

        $reader->close();

        return new Styles($styles);
    }
}
