<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Reader;

use Exception;
use RuntimeException;
use SaschaKliche\PhpXlsxReader\Model\AbstractRelationsModel;
use XMLReader;

abstract class AbstractRelationsReader extends AbstractReader
{
    protected const string RELATIONSHIP = 'Relationship';
    protected const string RELATIONSHIPS = 'Relationships';
    protected const string RELATIONSHIP_ID = 'Id';
    protected const string RELATIONSHIP_TARGET = 'Target';
    protected const string RELATIONSHIP_TYPE = 'Type';

    protected array $relationsById = [];

    /**
     * @throws Exception
     */
    protected function loadRelations(string $fileName): void
    {
        $reader = new XMLReader();
        if ($reader->open($fileName) === false) {
            throw new RuntimeException('Unable to open file: ' . $fileName);
        }

        /*
         * File layout (example: workbook relations)
         * <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
         * <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
         *  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>
         *  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
         *  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
         *  <Relationship Id="rId6" Type="http://schemas.microsoft.com/office/2017/10/relationships/person" Target="persons/person.xml"/>
         *  <Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
         *  <Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
         * </Relationships>
         */
        $readingRelationships = false;
        while ($reader->read()) {
            if ($reader->name === self::RELATIONSHIPS) {
                if ($reader->nodeType === XMLReader::ELEMENT) {
                    $readingRelationships = true;
                }
                if ($reader->nodeType === XMLReader::END_ELEMENT) {
                    $readingRelationships = false;
                }
                continue;
            }

            if (!$readingRelationships || $reader->name !== self::RELATIONSHIP || $reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }

            $attributes = $this->getAttributes($reader, [self::RELATIONSHIP_TYPE, self::RELATIONSHIP_ID, self::RELATIONSHIP_TARGET]);
            $type = $this->resolveType($attributes[self::RELATIONSHIP_TYPE] ?? '');
            $id = $attributes[self::RELATIONSHIP_ID] ?? null;
            $target = $attributes[self::RELATIONSHIP_TARGET] ?? null;

            if ($id === null || $target === null) {
                continue;
            }

            $relation = $this->buildModel($id, $target, $type);
            $this->relationsById[$id] = $relation;
        }
    }

    abstract protected function buildModel(string $id, string $target, int $type): AbstractRelationsModel;

    abstract protected function resolveType(string $type): int;
}
