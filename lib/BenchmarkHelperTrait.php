<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader;

trait BenchmarkHelperTrait
{
    protected float $durationInSeconds = 0;
    protected float $start;

    protected int $memoryUsage = 0;
    protected int $memoryPeakUsage = 0;

    public function getDurationInSeconds(): float
    {
        if (isset($this->workbookReader)) {
            return $this->workbookReader->getDurationInSeconds();
        }

        return $this->durationInSeconds;
    }

    public function getMemoryUsage(): int
    {
        if (isset($this->workbookReader)) {
            return $this->workbookReader->getMemoryUsage();
        }

        return $this->memoryUsage;
    }

    public function getMemoryPeakUsage(): int
    {
        if (isset($this->workbookReader)) {
            return $this->workbookReader->getMemoryPeakUsage();
        }

        return $this->memoryPeakUsage;
    }

    public function printPerformanceData(string $prepend = ''): void
    {
        if ($prepend !== '') {
            print $prepend . PHP_EOL;
        }
        print 'Duration: ' . $this->getDurationInSeconds() . ' seconds' . PHP_EOL;
        print 'Memory: ' . number_format($this->getMemoryUsage() / 1_048_576, 2) . ' MiB' . PHP_EOL;
        print 'Peak memory: ' . number_format($this->getMemoryPeakUsage() / 1_048_576, 2) . ' MiB' . PHP_EOL;
    }

    public function benchmarkStart(): void
    {
        $this->start = hrtime(true);
    }

    public function benchmarkStop(): void
    {
        $this->durationInSeconds = (hrtime(true) - $this->start) / 1e9;
        $this->memoryUsage = memory_get_usage();
        $this->memoryPeakUsage = memory_get_peak_usage();
    }
}
