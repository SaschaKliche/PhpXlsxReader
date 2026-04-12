<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Reader;

use Exception;
use Generator;
use RuntimeException;
use SaschaKliche\PhpXlsxReader\Configuration;
use SaschaKliche\PhpXlsxReader\Model\Cell;
use SaschaKliche\PhpXlsxReader\Model\Styles;
use SaschaKliche\PhpXlsxReader\Utils\Reference;
use XMLReader;

class WorksheetReader extends AbstractReader
{
    use CellValueHelper;

    protected const string PATH_SHEET_PREFIX = '#xl/';

    protected const string CELL_ELEMENT_NAME_VALUE = 'v'; // 18.3.1.96 v (Cell Value)
    protected const string CELL_ELEMENT_NAME_CELL_FORMULA = 'f';

    protected const string CELL_ATTRIBUTE_REFERENCE = 'r';
    // 18.3.1.4 c (Cell) - s (Style Index) - The index of this cell's style. Style records are stored in the Styles Part.
    // References 18.8.10 cellXfs (Cell Formats) ("Cells in the Sheet Part reference the xf records by zero-based index.")
    protected const string CELL_ATTRIBUTE_STYLE = 's';
    protected const string CELL_ATTRIBUTE_TYPE = 't';

    protected const string ELEMENT_NAME_CELL = 'c'; // 18.3.1.4 c (Cell)
    protected const string ELEMENT_NAME_HYPERLINK = 'hyperlink';
    protected const string ELEMENT_NAME_HYPERLINKS = 'hyperlinks';
    protected const string ELEMENT_NAME_ROW = 'row';
    protected const string ELEMENT_NAME_SHEET_DATA = 'sheetData';
    protected const string ELEMENT_NAME_WORKSHEET = 'worksheet';

    protected const string HYPERLINK_ATTRIBUTE_ID = 'r:id';
    protected const string HYPERLINK_ATTRIBUTE_DISPLAY = 'display';
    protected const string HYPERLINK_ATTRIBUTE_LOCATION = 'location';
    protected const string HYPERLINK_ATTRIBUTE_REF = 'ref';

    protected const string ROW_ATTRIBUTE_ROW_NUMBER = 'r';

    protected const string TYPE_VALUE_SHARED_STRING = 's';
    protected const string TYPE_VALUE_STRING = 'str';

    protected array $customFormats = [];
    protected bool $skipMissingCells;
    protected bool $skipMissingRows;
    protected bool $readFormulas;
    protected bool $readHyperlinks;
    protected bool $returnCellObjects;
    protected bool $useCellAddressAsIndex;
    protected bool $useDateSystem1900;
    protected array $columnsToLoad = [];
    protected array $rowsToLoad = [];
    protected array $hyperlinks = [];

    public function __construct(
        protected string $filePath,
        protected string $worksheetPath,
        protected string $worksheetName,
        protected SharedStringsReader $sharedStrings,
        protected Styles $styles,
        protected Configuration $configuration
    ) {
        $this->customFormats = $this->configuration->get(Configuration::CUSTOM_FORMATS);
        $this->skipMissingCells = $this->configuration->get(Configuration::SKIP_MISSING_CELLS);
        $this->skipMissingRows = $this->configuration->get(Configuration::SKIP_MISSING_ROWS);
        $this->readFormulas = $this->configuration->get(Configuration::READ_FORMULAS);
        $this->readHyperlinks = $this->configuration->get(Configuration::READ_HYPERLINKS);
        $this->returnCellObjects = $this->configuration->get(Configuration::RETURN_CELL_OBJECTS);
        $this->useDateSystem1900 = $this->configuration->get(Configuration::USE_DATE_SYSTEM_1900);
        $this->useCellAddressAsIndex = $this->configuration->get(Configuration::USE_CELL_ADDRESS);
        $this->initRowsOrColumnsToLoad(Configuration::COLUMNS_TO_LOAD, $this->columnsToLoad);
        $this->initRowsOrColumnsToLoad(Configuration::ROWS_TO_LOAD, $this->rowsToLoad);
    }

    /**
     * @throws Exception
     */
    public function load(): Generator
    {
        // filePath: /path/file.xlsx
        // worksheetPath: worksheets/sheet1.xml
        // result: zip:///path/file.xlsx#xl/worksheets/sheet1.xml
        $xmlFilePath = self::ZIP_URL . $this->filePath . self::PATH_SHEET_PREFIX . $this->worksheetPath;

        $this->handleHyperlinks($xmlFilePath);

        $reader = new XMLReader();
        if ($reader->open($xmlFilePath) === false) {
            throw new RuntimeException('Unable to open file: ' . $xmlFilePath);
        }

        while ($reader->read()) {
            if ($reader->name !== self::ELEMENT_NAME_SHEET_DATA) {
                continue;
            }

            if ($reader->nodeType === XMLReader::ELEMENT) {
                yield $this->handleSheetData($reader);
            }
        }

        $reader->close();
    }

    /**
     * The hyperlinks node is normally after the sheetData node so we have to read them first if they are needed.
     * @throws Exception
     */
    protected function handleHyperlinks(string $xmlFilePath): void
    {
        if (!$this->readHyperlinks || !WorksheetRelationsReader::exists($this->filePath, $this->worksheetPath)) {
            return;
        }

        $reader = new XMLReader();
        if ($reader->open($xmlFilePath) === false) {
            throw new RuntimeException('Unable to open file: ' . $xmlFilePath);
        }

        if (!$this->readUntil($reader, self::ELEMENT_NAME_WORKSHEET)) {
            $reader->close();
            return;
        }

        $reader->read();
        if (!$this->readUntil($reader, self::ELEMENT_NAME_HYPERLINKS)) {
            $reader->close();
            return;
        }

        $worksheetRelationsReader = WorksheetRelationsReader::from($this->filePath, $this->worksheetPath);
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::END_ELEMENT) {
                break;
            }

            if ($reader->name !== self::ELEMENT_NAME_HYPERLINK) {
                continue;
            }

            $attributes = $this->getAttributes($reader, [self::HYPERLINK_ATTRIBUTE_REF, self::HYPERLINK_ATTRIBUTE_ID, self::HYPERLINK_ATTRIBUTE_LOCATION, self::HYPERLINK_ATTRIBUTE_DISPLAY]);
            if (!isset($attributes[self::HYPERLINK_ATTRIBUTE_REF])) {
                continue;
            }

            if (isset($attributes[self::HYPERLINK_ATTRIBUTE_ID]) && $worksheetRelationsReader->hasRelationWithId($attributes[self::HYPERLINK_ATTRIBUTE_ID])) {
                // hyperlink targets to websites are stored in a relation
                $relation = $worksheetRelationsReader->getRelationWithId($attributes[self::HYPERLINK_ATTRIBUTE_ID]);
                $this->hyperlinks[$attributes[self::HYPERLINK_ATTRIBUTE_REF]] = $relation->getTarget();
                continue;
            }

            // link to cell does not have an r:id but a location
            $this->hyperlinks[$attributes[self::HYPERLINK_ATTRIBUTE_REF]] = ($attributes[self::HYPERLINK_ATTRIBUTE_LOCATION] ?? 'n/a');
        }

        $reader->close();
    }

    /**
     * @throws Exception
     */
    protected function handleSheetData(XmlReader $reader): Generator
    {
        $previousRowIndex = 0;
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === self::ELEMENT_NAME_SHEET_DATA) {
                break;
            }

            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== self::ELEMENT_NAME_ROW) {
                continue;
            }

            $attributes = $this->getAttributes($reader, [self::ROW_ATTRIBUTE_ROW_NUMBER]);
            $rowIndex = (int) $attributes[self::ROW_ATTRIBUTE_ROW_NUMBER];

            if (!$this->skipMissingRows) {
                // check for missing rows, add them if requested
                for ($i = $previousRowIndex + 1; $i < $rowIndex; $i++) {
                    if ($this->shouldLoadRowOrColumn($this->rowsToLoad, $i)) {
                        yield $i => [];
                    }
                }
            }
            $previousRowIndex = $rowIndex;

            if ($this->shouldLoadRowOrColumn($this->rowsToLoad, $rowIndex)) {
                yield $rowIndex => $this->handleRow($reader, $rowIndex);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function handleRow(XmlReader $reader, int $rowIndex): array
    {
        $cells = [];
        $previousCellIndex = 0;
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === self::ELEMENT_NAME_ROW) {
                break;
            }

            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== self::ELEMENT_NAME_CELL) {
                continue;
            }

            $attributes = $this->getAttributes($reader, [self::CELL_ATTRIBUTE_REFERENCE, self::CELL_ATTRIBUTE_TYPE, self::CELL_ATTRIBUTE_STYLE]);
            $originalCellAddress = $cellAddress = $attributes[self::CELL_ATTRIBUTE_REFERENCE];
            $cellIndex = Reference::convertCellAddressToIndex($cellAddress)[Reference::COLUMN];

            if (!$this->useCellAddressAsIndex ||! $this->skipMissingCells) {
                if (!$this->useCellAddressAsIndex) {
                    $cellAddress = $cellIndex;
                }

                if (!$this->skipMissingCells) {
                    // check for missing cells, add them if requested
                    for ($i = $previousCellIndex + 1; $i < $cellIndex; $i++) {
                        if (!$this->shouldLoadRowOrColumn($this->columnsToLoad, $i)) {
                            continue;
                        }

                        $inserted = $i;
                        if ($this->useCellAddressAsIndex) {
                            $inserted = Reference::convertIndexToCellAddress($rowIndex, $i);
                        }
                        $cells[$inserted] = null;
                    }
                    $previousCellIndex = $cellIndex;
                }
            }

            if (!$this->shouldLoadRowOrColumn($this->columnsToLoad, $cellIndex)) {
                continue;
            }

            $dataType = $attributes[self::CELL_ATTRIBUTE_TYPE] ?? '';
            $cellStyle = $attributes[self::CELL_ATTRIBUTE_STYLE] ?? '';
            $cellFormatString = '';
            $formatId = '';
            if ($cellStyle !== '') {
                $formatId = $this->styles->getNumberFormatId($cellStyle);
                $cellFormatString = $this->styles->getFormatString($cellStyle);
            }

            $cells[$cellAddress] = $this->handleCell($reader, $dataType, $formatId, $cellFormatString, $originalCellAddress);
        }

        return $cells;
    }

    /**
     * @throws Exception
     */
    protected function handleCell(XMLReader $reader, string $dataType, string $formatId, string $cellFormatString, string $originalCellAddress): mixed
    {
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === self::ELEMENT_NAME_CELL) {
                break;
            }

            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }

            if ($this->readFormulas && $reader->name === self::CELL_ELEMENT_NAME_CELL_FORMULA) {
                $formula = $reader->readString();
            }

            if ($reader->name !== self::CELL_ELEMENT_NAME_VALUE) {
                continue;
            }

            $rawValue = $reader->readString();
            $cellValue = $this->convertRawValue($rawValue, $dataType, $cellFormatString);

            if ($cellFormatString !== '' && array_key_exists($cellFormatString, $this->customFormats)) {
                $cellValue = $this->customFormats[$cellFormatString]($cellValue, $rawValue);
            } elseif ($formatId !== '' && array_key_exists($formatId, $this->customFormats)) {
                $cellValue = $this->customFormats[$formatId]($cellValue, $rawValue);
            }
        }

        // Caution! $rawValue and $cellValue can be null if cells are joined and don't contain a "v" element

        if ($this->returnCellObjects === true) {
            return new Cell(
                $originalCellAddress,
                $rawValue ?? '',
                $cellValue ?? null,
                $formula ?? null,
                $cellFormatString,
                $this->hyperlinks[$originalCellAddress] ?? null,
            );
        }

        return $cellValue ?? null;
    }

    protected function initRowsOrColumnsToLoad(int $configurationVar, array &$configuration): void
    {
        $toLoad = $this->configuration->get($configurationVar);
        if (!array_is_list($toLoad)) {
            $toLoad = $toLoad[$this->worksheetName] ?? [];
        }

        if (array_is_list($toLoad)) {
            $configuration = array_flip($toLoad);
            return;
        }

        // still an associative array, index should be an operator
        foreach ($toLoad as $operator => $value) {
            if ($value < 1) {
                throw new RuntimeException(
                    'Invalid valid "' . $value . '" for operator "' . $operator . '" in ' .
                        ($configurationVar === Configuration::ROWS_TO_LOAD ? 'ROWS_TO_LOAD' : 'COLUMNS_TO_LOAD')
                );
            }

            if ($operator === Configuration::MAX) {
                $configuration[Configuration::MAX] = $value;
                continue;
            }

            if ($operator === Configuration::MIN) {
                $configuration[Configuration::MIN] = $value;
                continue;
            }

            throw new RuntimeException(
                'Invalid operator "' . $operator . '" in ' .
                    ($configurationVar === Configuration::ROWS_TO_LOAD ? 'ROWS_TO_LOAD' : 'COLUMNS_TO_LOAD')
            );
        }
    }

    protected function shouldLoadRowOrColumn(array $configuration, int $index): bool
    {
        if (isset($configuration[Configuration::MIN])) {
            return ($configuration[Configuration::MIN] === 1 || $index >= $configuration[Configuration::MIN]);
        }

        if (isset($configuration[Configuration::MAX])) {
            return ($configuration[Configuration::MAX] === 0 || $index <= $configuration[Configuration::MAX]);
        }

        if ($configuration === []) {
            return true;
        }

        if (array_key_exists($index, $configuration)) {
            return true;
        }

        return false;
    }
}
