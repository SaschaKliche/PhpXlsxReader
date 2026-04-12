<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader;

class Configuration
{
    public const int CUSTOM_FORMATS = 1;
    public const int SKIP_MISSING_CELLS = 2;
    public const int SKIP_MISSING_ROWS = 3;
    public const int RETURN_CELL_OBJECTS = 4;
    public const int READ_FORMULAS = 41;
    public const int READ_HYPERLINKS = 42;
    public const int USE_CELL_ADDRESS = 5;
    public const int USE_DATE_SYSTEM_1900 = 6;
    public const int COLUMNS_TO_LOAD = 7;
    public const int ROWS_TO_LOAD = 8;
    public const int WORKSHEETS_TO_LOAD = 9;

    protected array $configuration = [
        self::CUSTOM_FORMATS => [],
        self::RETURN_CELL_OBJECTS => false,
        self::READ_FORMULAS => false,
        self::READ_HYPERLINKS => false,
        self::SKIP_MISSING_CELLS => true,
        self::SKIP_MISSING_ROWS => true,
        self::USE_CELL_ADDRESS => true,
        self::USE_DATE_SYSTEM_1900 => true,
        self::COLUMNS_TO_LOAD => [],
        self::ROWS_TO_LOAD => [],
        self::WORKSHEETS_TO_LOAD => [],
    ];

    public const string MAX = 'max';
    public const string MIN = 'min';

    public static function from(array $configurationMap): self
    {
        $configuration = new self();

        foreach ($configurationMap as $key => $value) {
            $configuration->set($key, $value);
        }

        return $configuration;
    }

    public function get(int|string $name, mixed $default = null): mixed
    {
        return $this->configuration[$name] ?? $default;
    }

    public function set(int|string $name, mixed $value): self
    {
        $this->configuration[$name] = $value;
        return $this;
    }
}
