<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests;

use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    public const string INPUT_FILES_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'input' . DIRECTORY_SEPARATOR;
}
