<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Model;

class WorksheetRelation extends AbstractRelationsModel
{
    public const int TYPE_HYPERLINK = 10;

    public const string TYPE_UNKNOWN_LABEL = 'unknown';
    public const string TYPE_HYPERLINK_LABEL = 'hyperlink';

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_HYPERLINK => self::TYPE_HYPERLINK_LABEL,
            self::TYPE_UNKNOWN => self::TYPE_UNKNOWN_LABEL,
        };
    }
}
