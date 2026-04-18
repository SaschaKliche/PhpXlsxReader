<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Model;

class WorkbookRelation extends AbstractRelation
{
    public const int TYPE_PERSON = 10;
    public const int TYPE_SHARED_STRINGS = 20;
    public const int TYPE_STYLES = 30;
    public const int TYPE_THEME = 40;
    public const int TYPE_WORKSHEET = 50;

    public const string TYPE_UNKNOWN_LABEL = 'unknown';
    public const string TYPE_PERSON_LABEL = 'person';
    public const string TYPE_SHARED_STRINGS_LABEL = 'shared strings';
    public const string TYPE_STYLES_LABEL = 'styles';
    public const string TYPE_THEME_LABEL = 'theme';
    public const string TYPE_WORKSHEET_LABEL = 'worksheet';

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PERSON => self::TYPE_PERSON_LABEL,
            self::TYPE_SHARED_STRINGS => self::TYPE_SHARED_STRINGS_LABEL,
            self::TYPE_STYLES => self::TYPE_STYLES_LABEL,
            self::TYPE_THEME => self::TYPE_THEME_LABEL,
            self::TYPE_WORKSHEET => self::TYPE_WORKSHEET_LABEL,
            self::TYPE_UNKNOWN => self::TYPE_UNKNOWN_LABEL,
        };
    }
}
