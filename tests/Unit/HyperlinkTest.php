<?php /** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SaschaKliche\PhpXlsxReader\Model\Cell;
use SaschaKliche\PhpXlsxReader\Tests\AbstractTestCase;
use SaschaKliche\PhpXlsxReader\XlsxReader;

class HyperlinkTest extends AbstractTestCase
{
    #[Test]
    function it_returns_hyperlinks_from_cell_objects()
    {
        $reader = new XlsxReader();
        $reader->open(self::INPUT_FILES_DIR . 'Hyperlink.xlsx');

        $data = $reader->returnCellObjects(readHyperlinks: true)->readAsArray();

        self::assertInstanceOf(Cell::class, $data['Tabelle1'][1]['A1']);

        self::assertEquals('This cell has a hyperlink', $data['Tabelle1'][1]['A1']);
        self::assertEquals('https://www.tagesschau.de/', $data['Tabelle1'][1]['A1']->getHyperLinkTarget());

        self::assertNull($data['Tabelle2'][1]['A1']->getHyperLinkTarget());

        self::assertEquals('This is a hyperlink on a different sheet', $data['Tabelle2'][5]['B5']);
        self::assertEquals('https://www.heise.de/', $data['Tabelle2'][5]['B5']->getHyperLinkTarget());

        self::assertEquals('Link to a cell', $data['Tabelle2'][20]['E20']);
        self::assertEquals('Tabelle1!A1', $data['Tabelle2'][20]['E20']->getHyperLinkTarget());
    }
}
