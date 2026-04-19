<?php /** @noinspection HttpUrlsUsage */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Reader;

use Exception;
use SaschaKliche\PhpXlsxReader\Model\AbstractRelation;
use SaschaKliche\PhpXlsxReader\Model\WorksheetRelation;

class WorksheetRelationsReader extends AbstractRelationsReader
{
    public const string PATH_RELATIONSHIPS = '#xl/worksheets/_rels/';

    public const int TYPE_UNKNOWN = AbstractRelation::TYPE_UNKNOWN;
    public const int TYPE_HYPERLINK = WorksheetRelation::TYPE_HYPERLINK;

    protected const string SCHEMA_HYPERLINk = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink';

    /** @var WorksheetRelation[] */
    protected array $relationsById = [];

    /**
     * @throws Exception
     */
    public static function from(string $filePath, string $worksheetPath): self
    {
        // filePath: /path/file.xlsx
        // worksheetPath: worksheets/sheet1.xml
        // result: zip:///path/file.xlsx#xl/worksheets/_rels/sheet1.xml.rels

        $reader = new self();
        $reader->loadRelations(self::ZIP_URL . $filePath . self::PATH_RELATIONSHIPS . basename($worksheetPath) . '.rels');

        return $reader;
    }

    public static function exists(string $filePath, string $worksheetPath): bool
    {
        // filePath: /path/file.xlsx
        // worksheetPath: worksheets/sheet1.xml
        // result: #xl/worksheets/_rels/sheet1.xml.rels
        return self::zipEntryExists($filePath, self::PATH_RELATIONSHIPS . basename($worksheetPath) . '.rels');
    }

    protected function resolveType(string $type): int
    {
        return match ($type) {
            self::SCHEMA_HYPERLINk => self::TYPE_HYPERLINK,
            default => self::TYPE_UNKNOWN,
        };
    }

    protected function buildModel(string $id, string $target, int $type): WorksheetRelation
    {
        return new WorksheetRelation($id, $target, $type);
    }

    public function getRelationWithId(string $id): WorksheetRelation
    {
        return $this->relationsById[$id];
    }

    public function hasRelationWithId(string $id): bool
    {
        return isset($this->relationsById[$id]);
    }
}
