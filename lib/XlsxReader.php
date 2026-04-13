<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader;

use Exception;
use Generator;
use RuntimeException;
use SaschaKliche\PhpXlsxReader\Model\Metadata;
use SaschaKliche\PhpXlsxReader\Model\Styles;
use SaschaKliche\PhpXlsxReader\Reader\AbstractReader;
use SaschaKliche\PhpXlsxReader\Reader\MetadataReader;
use SaschaKliche\PhpXlsxReader\Reader\SharedStringsReader;
use SaschaKliche\PhpXlsxReader\Reader\StylesReader;
use SaschaKliche\PhpXlsxReader\Reader\WorkbookRelationsReader;
use SaschaKliche\PhpXlsxReader\Reader\WorksheetReader;
use XMLReader;

class XlsxReader extends AbstractReader
{
    use BenchmarkHelperTrait, ConfigurationHelperTrait;

    public const string VERSION = '1.1.0-develop';

    protected const string PATH_WORKBOOK = '#xl/workbook.xml';

    protected const string WORKBOOK_ELEMENT_SHEET = 'sheet';
    protected const string WORKBOOK_ELEMENT_SHEETS = 'sheets';
    protected const string WORKBOOK_ELEMENT_WORKBOOK_PR = 'workbookPr';
    protected const string WORKBOOK_WORKBOOK_PR_ATTRIBUTE = 'date1904';

    protected const string WORKBOOK_SHEET_ATTRIBUTE_NAME = 'name';
    protected const string WORKBOOK_SHEET_ATTRIBUTE_RELATIONSHIP_ID = 'id';
    protected const string WORKBOOK_SHEET_ATTRIBUTE_RELATIONSHIP_NAMESPACE = 'r';

    protected string $filePath;
    protected bool $workbookOpened = false;
    protected bool $worksheetsLoaded = false;
    /** @var string[] */
    protected array $worksheetNames = [];

    protected Configuration $configuration;
    protected ?Metadata $metadata = null;
    protected SharedStringsReader $sharedStrings;
    protected Styles $styles;
    protected WorkbookRelationsReader $workbookRelations;

    public function __construct(array $configuration = [])
    {
        $this->configuration = Configuration::from($configuration);
    }

    /*
     * Load the workbook.xml to determine the date format used and
     * retrieve the names and relationship IDs of the worksheets.
     */
    public function open(string $filePath): void
    {
        $start = hrtime(true);

        $this->filePath = $filePath;
        $workbookFileName = self::ZIP_URL . $filePath . self::PATH_WORKBOOK;

        $reader = new XMLReader();
        if ($reader->open($workbookFileName) === false) {
            throw new RuntimeException('Unable to open file: ' . $workbookFileName);
        }

        $this->metadata = null;
        $this->worksheetNames = [];

        $readingSheets = false;
        while ($reader->read()) {
            if ($reader->name === self::WORKBOOK_ELEMENT_WORKBOOK_PR) {
                $attribute = $reader->getAttribute(self::WORKBOOK_WORKBOOK_PR_ATTRIBUTE);
                if ($attribute !== null) {
                    $this->configuration->set(Configuration::USE_DATE_SYSTEM_1900, false);
                }
                continue;
            }

            if ($reader->name === self::WORKBOOK_ELEMENT_SHEETS) {
                if ($reader->nodeType === XMLREADER::ELEMENT) {
                    $readingSheets = true;
                }
                if ($reader->nodeType === XMLREADER::END_ELEMENT) {
                    $readingSheets = false;
                }
            }

            if ($reader->name !== self::WORKBOOK_ELEMENT_SHEET) {
                continue;
            }

            if (!$readingSheets) {
                throw new RuntimeException('sheet element outside sheets');
            }

            $worksheetName = $reader->getAttribute(self::WORKBOOK_SHEET_ATTRIBUTE_NAME);
            $attributeName = self::WORKBOOK_SHEET_ATTRIBUTE_RELATIONSHIP_NAMESPACE . ':' .
                self::WORKBOOK_SHEET_ATTRIBUTE_RELATIONSHIP_ID;
            $attributes = $this->getAttributes($reader, [$attributeName]);
            $sheetRelationshipId = $attributes[$attributeName] ?? null;
            if ($sheetRelationshipId === null) {
                throw new RuntimeException('Missing attribute "' . $attributeName . '" for sheet element');
            }

            $this->worksheetNames[$worksheetName] = $sheetRelationshipId;
        }

        $reader->close();
        $this->workbookOpened = true;

        $this->durationInSeconds = (hrtime(true) - $start) / 1e9;
        $this->memoryUsage = memory_get_usage();
        $this->memoryPeakUsage = memory_get_peak_usage();
    }

    /**
     * Read the workbook's data ...
     * - load the workbook relations to get the worksheet file names
     * - load the shared strings file
     * - load styles file to get cell format information
     * - use a worksheet reader for each worksheet file to retrieve the cell's data
     *
     * @throws Exception
     */
    public function read(): Generator
    {
        if (!$this->workbookOpened) {
            throw new RuntimeException('The file must have been opened before calling this method');
        }

        $this->benchmarkStart();

        $this->workbookRelations = WorkbookRelationsReader::from($this->filePath);

        $this->sharedStrings = SharedStringsReader::from($this->filePath);

        $this->styles = StylesReader::from($this->filePath);

        $worksheetsToLoad = array_flip($this->configuration->get(Configuration::WORKSHEETS_TO_LOAD));

        foreach ($this->worksheetNames as $worksheetName => $sheetRelationshipId) {
            if ($worksheetsToLoad !== [] && !array_key_exists($worksheetName, $worksheetsToLoad)) {
                continue;
            }

            $worksheet = new WorksheetReader(
                $this->filePath,
                $this->workbookRelations->getRelationWithId($sheetRelationshipId)->getTarget(),
                $worksheetName,
                $this->sharedStrings,
                $this->styles,
                $this->configuration
            );

            foreach ($worksheet->load() as $row) {
                yield $worksheetName => $row;
            }
        }

        $this->worksheetsLoaded = true;

        $this->benchmarkStop();
    }

    /**
     * @throws Exception
     */
    public function readAsArray(): array
    {
        $data = [];
        foreach ($this->read() as $worksheetName => $rows) {
            $data[$worksheetName] = [];

            foreach ($rows as $rowIndex => $row) {
                $data[$worksheetName][$rowIndex] = [];

                foreach ($row as $cellAddress => $cell) {
                    $data[$worksheetName][$rowIndex][$cellAddress] = $cell;
                }
            }
        }

        return $data;
    }

    /**
     * @throws Exception
     */
    public function readWithHeader(int|array $headerRowIndex = 0): array
    {
        $this->configuration->set(Configuration::USE_CELL_ADDRESS, false);
        $workbookData = $this->readAsArray();

        foreach ($workbookData as $worksheetName => &$rows) {
            if ($rows === []) {
                continue;
            }

            $currentHeaderRowIndex = $headerRowIndex[$worksheetName] ?? $headerRowIndex;
            if (is_int($currentHeaderRowIndex) && $currentHeaderRowIndex < 0) {
                throw new RuntimeException('Header row index must be >= 0');
            }

            if ($currentHeaderRowIndex !== 0) {
                if (!array_key_exists($currentHeaderRowIndex, $rows)) {
                    throw new RuntimeException("Header row index '$currentHeaderRowIndex' does not exist");
                }
                $header = $rows[$currentHeaderRowIndex];
                unset($rows[$currentHeaderRowIndex]);
            } else {
                // use the first available row as header row
                $firstKey = array_key_first($rows);
                $header = $rows[$firstKey];
                unset($rows[$firstKey]);
            }

            foreach ($rows as $rowIndex => &$row) {

                foreach ($row as $column => $value) {
                    if (!isset($header[$column])) {
                        throw new RuntimeException(
                            'No header exists for column ' . $column .
                            ' of row ' . $rowIndex . ' on worksheet "' . $worksheetName . '"'
                        );
                    }

                    $row[$header[$column]] = $value;
                    unset($row[$column]);
                }
            }
        }

        return $workbookData;
    }

    public function getMetadata(): Metadata
    {
        if ($this->metadata === null) {
            if (!$this->workbookOpened) {
                throw new RuntimeException('The file must have been opened before calling this method');
            }

            $this->metadata = MetadataReader::from($this->filePath);
        }

        return $this->metadata;
    }

    /**
     * @return string[]
     */
    public function getWorksheetNames(): array
    {
        if (!$this->workbookOpened) {
            throw new RuntimeException('The file must have been opened before calling this method');
        }

        return array_keys($this->worksheetNames);
    }

    /**
     * @internal
     * For testing purposes only.
     * Subject to removal without further notice.
     */
    public function dateSystem1900(): bool
    {
        return $this->configuration->get(Configuration::USE_DATE_SYSTEM_1900);
    }

    /**
     * @internal
     * For testing purposes only.
     * Subject to removal without further notice.
     */
    public function worksheetsHaveBeenLoaded(): bool
    {
        return $this->worksheetsLoaded;
    }
}
