<?php /** @noinspection HttpUrlsUsage */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Reader;

use Exception;
use RuntimeException;
use SaschaKliche\PhpXlsxReader\Model\AbstractRelation;
use SaschaKliche\PhpXlsxReader\Model\WorkbookRelation;

class WorkbookRelationsReader extends AbstractRelationsReader
{
    public const string PATH_RELATIONSHIPS = '#xl/_rels/workbook.xml.rels';

    public const int TYPE_UNKNOWN = AbstractRelation::TYPE_UNKNOWN;
    public const int TYPE_PERSON = WorkbookRelation::TYPE_PERSON;
    public const int TYPE_SHARED_STRINGS = WorkbookRelation::TYPE_SHARED_STRINGS;
    public const int TYPE_STYLES = WorkbookRelation::TYPE_STYLES;
    public const int TYPE_THEME = WorkbookRelation::TYPE_THEME;
    public const int TYPE_WORKSHEET = WorkbookRelation::TYPE_WORKSHEET;

    protected const string SCHEMA_PERSON =
        'http://schemas.microsoft.com/office/2017/10/relationships/person';
    protected const string SCHEMA_SHARED_STRINGS =
        'http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings';
    protected const string SCHEMA_STYLE =
        'http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles';
    protected const string SCHEMA_THEME =
        'http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme';
    protected const string SCHEMA_WORKSHEET =
        'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet';

    /** @var WorkbookRelation[] */
    protected array $relationsById = [];

    /**
     * @throws Exception
     */
    public static function from(string $fileName): self
    {
        if (!self::zipEntryExists($fileName, self::PATH_RELATIONSHIPS)) {
            throw new RuntimeException('File "' . $fileName . '" does not exist.');
        }

        $reader = new self();
        $reader->loadRelations(self::ZIP_URL . $fileName . self::PATH_RELATIONSHIPS);

        return $reader;
    }

    protected function resolveType(string $type): int
    {
        return match ($type) {
            self::SCHEMA_PERSON => self::TYPE_PERSON,
            self::SCHEMA_SHARED_STRINGS => self::TYPE_SHARED_STRINGS,
            self::SCHEMA_STYLE => self::TYPE_STYLES,
            self::SCHEMA_THEME => self::TYPE_THEME,
            self::SCHEMA_WORKSHEET => self::TYPE_WORKSHEET,
            default => self::TYPE_UNKNOWN,
        };
    }

    protected function buildModel(string $id, string $target, int $type): WorkbookRelation
    {
        return new WorkbookRelation($id, $target, $type);
    }

    public function getRelationWithId(string $id): WorkbookRelation
    {
        return $this->relationsById[$id];
    }
}
