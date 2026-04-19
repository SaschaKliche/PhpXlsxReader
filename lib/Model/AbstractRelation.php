<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Model;

abstract class AbstractRelation
{
    public const int TYPE_UNKNOWN = 0;

    public function __construct(protected string $id, protected string $target, protected int $type)
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return __CLASS__ . $this->getId() . ' (' . $this->getTypeLabel() . ') => ' . $this->getTarget();
    }

    abstract public function getTypeLabel(): string;
}
