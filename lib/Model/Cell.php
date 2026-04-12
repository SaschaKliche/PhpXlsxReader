<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Model;

use DateTime;
use Exception;
use SaschaKliche\PhpXlsxReader\Utils\Reference;

class Cell
{
    public const int DATA_TYPE_UNKNOWN = 0;
    public const int DATA_TYPE_DATETIME = 10;
    public const int DATA_TYPE_FLOAT = 20;
    public const int DATA_TYPE_INTEGER = 30;
    public const int DATA_TYPE_STRING = 40;

    protected int $columnIndex;
    protected int $rowIndex;
    protected int $dataType;

    /**
     * @throws Exception
     */
    public function __construct(
        protected string $address,
        protected string $rawValue,
        protected mixed $value,
        protected string|null $formula,
        protected string $cellFormatString,
        protected string|null $hyperlinkTarget,
    ) {

        $reference = Reference::convertCellAddressToIndex($address);
        $this->columnIndex = $reference[Reference::COLUMN];
        $this->rowIndex = $reference[Reference::ROW];

        if ($this->value instanceof DateTime) {
            $this->dataType = self::DATA_TYPE_DATETIME;
            return;
        }

        if (is_string($this->value)) {
            $this->dataType = self::DATA_TYPE_STRING;
            return;
        }

        if (is_float($this->value)) {
            $this->dataType = self::DATA_TYPE_FLOAT;
            return;
        }

        if (is_int($this->value)) {
            $this->dataType = self::DATA_TYPE_INTEGER;
            return;
        }

        $this->dataType = self::DATA_TYPE_UNKNOWN;
    }

    public function __toString(): string
    {
        return ($this->value instanceof DateTime) ?
            $this->value->format('Y-m-d H:i:s') :
            (string) $this->value;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getCellFormatString(): string
    {
        return $this->cellFormatString;
    }

    public function getColumnIndex(): int
    {
        return $this->columnIndex;
    }

    public function getDataType(): int
    {
        return $this->dataType;
    }

    public function getFormula(): string|null
    {
        return $this->formula;
    }

    public function getHyperlinkTarget(): string|null
    {
        return $this->hyperlinkTarget;
    }

    public function getRawValue(): string
    {
        return $this->rawValue;
    }

    public function getRowIndex(): int
    {
        return $this->rowIndex;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
