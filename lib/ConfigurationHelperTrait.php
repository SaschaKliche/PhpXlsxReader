<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader;

trait ConfigurationHelperTrait
{
    public function customFormats(array $formats): self
    {
        $this->configuration->set(Configuration::CUSTOM_FORMATS, $formats);
        return $this;
    }

    public function includeMissingCells(): self
    {
        $this->configuration->set(Configuration::SKIP_MISSING_CELLS, false);
        return $this;
    }

    public function skipMissingCells(): self
    {
        $this->configuration->set(Configuration::SKIP_MISSING_CELLS, true);
        return $this;
    }

    public function includeMissingRows(): self
    {
        $this->configuration->set(Configuration::SKIP_MISSING_ROWS, false);
        return $this;
    }

    public function skipMissingRows(): self
    {
        $this->configuration->set(Configuration::SKIP_MISSING_ROWS, true);
        return $this;
    }

    public function returnCellObjects(bool $readFormulas = false, bool $readHyperlinks = false): self
    {
        $this->configuration->set(Configuration::RETURN_CELL_OBJECTS, true);
        $this->configuration->set(Configuration::READ_FORMULAS, $readFormulas);
        $this->configuration->set(Configuration::READ_HYPERLINKS, $readHyperlinks);
        return $this;
    }

    public function useCellAddress(): self
    {
        $this->configuration->set(Configuration::USE_CELL_ADDRESS, true);
        return $this;
    }

    public function useColumnIndex(): self
    {
        $this->configuration->set(Configuration::USE_CELL_ADDRESS, false);
        return $this;
    }

    public function useDateSystem1900(): self
    {
        $this->configuration->set(Configuration::USE_DATE_SYSTEM_1900, true);
        return $this;
    }

    public function useDateSystem1904(): self
    {
        $this->configuration->set(Configuration::USE_DATE_SYSTEM_1900, false);
        return $this;
    }

    public function columns(array $columns): self
    {
        $this->configuration->set(Configuration::COLUMNS_TO_LOAD, $columns);
        return $this;
    }

    public function rows(array $rows): self
    {
        $this->configuration->set(Configuration::ROWS_TO_LOAD, $rows);
        return $this;
    }

    public function worksheets(array $worksheetNames): self
    {
        $this->configuration->set(Configuration::WORKSHEETS_TO_LOAD, $worksheetNames);
        return $this;
    }
}
