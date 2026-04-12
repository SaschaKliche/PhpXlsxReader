<?php
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Model;

class Styles
{
    protected const string INDEX_NUMBER_FORMAT_IDS = 'numberFormatIds';
    protected const string INDEX_NUMBER_FORMATS = 'numberFormats';

    /**
     * ECMA-376 Fifth Edition, Part 1, 18.8.30 numFmt (Number Format), Page 1776
     * https://www.ecma-international.org/publications-and-standards/standards/ecma-376/
     */
    protected const array BUILTIN_FORMATS = [
        '0' => 'General',
        '1' => '0',
        '2' => '0.00',
        '3' => '#,##0',
        '4' => '#,##0.00',
        '9' => '0%',
        '10' => '0.00%',
        '11' => '0.00E+00',
        '12' => '# ?/?',
        '13' => '# ??/??',
        '14' => 'mm-dd-yy',
        '15' => 'd-mmm-yy',
        '16' => 'd-mmm',
        '17' => 'mmm-yy',
        '18' => 'h:mm AM/PM',
        '19' => 'h:mm:ss AM/PM',
        '20' => 'h:mm',
        '21' => 'h:mm:ss',
        '22' => 'm/d/yy h:mm',
        '37' => '#,##0 ;(#,##0)',
        '38' => '#,##0 ;[Red](#,##0)',
        '39' => '#,##0.00;(#,##0.00)',
        '40' => '#,##0.00;[Red](#,##0.00)',
        '45' => 'mm:ss',
        '46' => '[h]:mm:ss',
        '47' => 'mmss.0',
        '48' => '##0.0E+0',
        '49' => '@',
    ];

    /**
     * @param array<string, array<int, string>> $styles
     *
     *  Index is ...
     *  - INDEX_NUMBER_FORMATS contains an mapping of numFmtId to number format string
     *  - INDEX_NUMBER_FORMAT_IDS contains a list of numFmtIds
     *  - INDEX_CELL_FORMATS contains a list of cell formats (e.g. cell alignment, font, then number format used)
     */
    public function __construct(protected array $styles) {
    }

    public function getBuiltInStyle(string $styleName): string
    {
        return self::BUILTIN_FORMATS[$styleName] ?? '';
    }

    public function hasBuiltInStyle(string $styleName): bool
    {
        return isset(self::BUILTIN_FORMATS[$styleName]);
    }

    public function getNumberFormatId(string $index): string
    {
        return $this->styles[self::INDEX_NUMBER_FORMAT_IDS][$index] ?? '';
    }

    public function hasNumberFormatId(string $index): bool
    {
        return isset($this->styles[self::INDEX_NUMBER_FORMAT_IDS][$index]);
    }

    public function getNumberFormat(string $formatName): string
    {
        return $this->styles[self::INDEX_NUMBER_FORMATS][$formatName] ?? '';
    }

    public function hasNumberFormat(string $formatName): bool
    {
        return isset($this->styles[self::INDEX_NUMBER_FORMATS][$formatName]);
    }

    public function getFormatString(string $index): string
    {
        if (!$this->hasNumberFormatId($index)) {
            return '';
        }

        // ECMA-376 Fifth Edition, Part 1, 18.8.30 numFmt (Number Format), Page 1776
        // https://www.ecma-international.org/publications-and-standards/standards/ecma-376/
        $numberFormatId = $this->getNumberFormatId($index);

        $format = 'n/a';
        if ($this->hasBuiltInStyle($numberFormatId)) {
            $format = $this->getBuiltInStyle($numberFormatId);
        }

        if ($this->hasNumberFormat($numberFormatId)) {
            $format = $this->getNumberFormat($numberFormatId);
        }

        return $format;
    }
}
