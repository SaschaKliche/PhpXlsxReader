<?php /** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SaschaKliche\PhpXlsxReader\Model\Cell;
use SaschaKliche\PhpXlsxReader\Tests\AbstractTestCase;
use SaschaKliche\PhpXlsxReader\XlsxReader;

class CellObjectsTest extends AbstractTestCase
{
    #[Test]
    function it_returns_cell_objects_including_formulas_and_hyperlinks()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        foreach ($reader->returnCellObjects(readFormulas: true, readHyperlinks: true)->read() as $worksheetName => $rows) {
            self::assertIsString($worksheetName);

            foreach ($rows as $rowIndex => $row) {
                self::assertIsInt($rowIndex);

                foreach ($row as $cellAddress => $cell) {
                    self::assertIsString($cellAddress);
                    self::assertInstanceOf(Cell::class, $cell);
                    self::assertIsString($cell->getAddress());
                    self::assertIsString($cell->getCellFormatString());
                    if ($cell->getFormula() !== null) {
                        self::assertIsString($cell->getFormula());
                    }
                    if ($cell->getHyperlinkTarget() !== null) {
                        self::assertIsString($cell->getHyperlinkTarget());
                    }
                    self::assertIsString($cell->getRawValue());
                    self::assertIsInt($cell->getColumnIndex());
                    self::assertIsInt($cell->getRowIndex());
                    self::assertIsInt($cell->getDataType());
                }
            }
        }
    }

    #[Test]
    function it_returns_cell_objects_without_formulas_and_hyperlinks()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Basic.xlsx');

        foreach ($reader->returnCellObjects()->read() as $worksheetName => $rows) {
            self::assertIsString($worksheetName);

            foreach ($rows as $rowIndex => $row) {
                self::assertIsInt($rowIndex);

                foreach ($row as $cellAddress => $cell) {
                    self::assertIsString($cellAddress);
                    self::assertInstanceOf(Cell::class, $cell);
                    self::assertIsString($cell->getAddress());
                    self::assertIsString($cell->getCellFormatString());
                    self::assertNull($cell->getFormula());
                    self::assertNull($cell->getHyperlinkTarget());
                    self::assertIsString($cell->getRawValue());
                    self::assertIsInt($cell->getColumnIndex());
                    self::assertIsInt($cell->getRowIndex());
                    self::assertIsInt($cell->getDataType());
                }
            }
        }
    }
}
