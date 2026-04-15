# XlsxReader

This library provides a simple interface to read Office Open XML (OOXML) spreadsheet files (XLSX).

## Main features
- Read the whole file into an [array](#readasarray-reading-a-workbook-into-an-array) 
  or read a file [row by row](#read-reading-a-workbook-using-generators)
  to reduce memory consumption
- Read a file containing header rows into an [associative array](#readwithheader-reading-a-workbook-with-header-row-as-an-associative-array)
  (think CSV)
- Read only specific [worksheets](#worksheets-selecting-the-worksheets-to-load-from-a-workbook),
  [rows](#rows-selecting-the-rows-to-load-from-the-worksheets),
  [columns](#columns-selecting-the-columns-to-load-from-the-worksheets)
- [Include](#includemissingrows-include-missing-rows)
  or [skip](#skipmissingrows-ignore-missing-rows)
  missing row
- [Include](#includemissingcells-include-missing-cells)
  or [skip](#skipmissingcells-ignore-missing-cells)
  missing columns
- Read only the cell values or additional information like raw values, formulas, hyperlinks, and format strings
  by using [`Cell` objects](#returncellobjects-return-cell-object-instances-instead-of-cell-values)
- Cell values are returned as string, integer, float, or DateTime by default, based on the cell contents
- Use [custom formatter closures](#customformats-apply-custom-formatting)
- Use [cell addresses](#usecelladdress-indexing-cells-by-cell-address)
  (e.g. E17)
  or [column indexes](#usecolumnindex-indexing-cells-by-column-index-number)
  (e.g. 5)
- Retrieve [metadata](#getmetadata-retrieve-workbook-metadata) without reading the whole file
- Retrieve [worksheet names](#getworksheetnames-retrieve-the-worksheet-names-of-a-workbook) without reading the whole file
- [Configuration](#configuration) via array or fluent helper methods

> [!NOTE]
> The library is not optimized to handle large files. It might use excessive memory and CPU time.
> See [performance considerations](#performance-considerations) for further information.

> [!IMPORTANT]
> The library is not hardened against malformed files, this could create security issues.

## License

This library is licensed under the [MIT license](https://opensource.org/license/mit).
See the `LICENSE` file for details.

## Requirements

The library requires at least PHP 8.3 with the following extensions:
* ctype
* xmlreader
* zip

PHP 8.3, PHP 8.4, and PHP 8.5 are supported.

## Installation via Composer

Installation is quick & easy with [Composer](https://getcomposer.org/):

```bash
composer require saschakliche/phpxlsxreader
```

## Overview

By default, the library reads all worksheets in a given file but that can be configured.
For each available row each available cell will be read.
Available means that the row/cell does exist in the input file.
Rows or cells that do not exist in the input file will not be returned at all.
That means that the data returned can contain non-consecutive rows/cells.

The libraries' main class is `XlsxReader`:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();
$reader->open(<pathToInputFile>);

foreach ($reader->read() as $worksheetName => $rows) {
    print 'Worksheet ' . $worksheetName . PHP_EOL;
    foreach ($rows as $rowIndex => $row) {
        print "\t" . 'Row ' . $rowIndex . PHP_EOL;
        foreach ($row as $columnAddress => $column) {
            // $column can be int/float/string/DateTime unless a custom formatter is used
            print "\t\t" . 'Cell ' . $columnAddress . ': ' . ($column instanceof DateTime ? $column->format('Y-m-d H:i:s') : $column) . "\n";
        }
    }
}
```

## `XlsxReader` API

The following methods are provided by `XlsxReader`:
```php
// opening a file
open(string $filePath): XlsxReader

// reading the actual data
read(): Generator
readAsArray(): array
readWithHeader(int|array $headerRowIndex = 0): array

// retrieving information about the workbook
getHeaders(string $worksheetName = ''): array
getMetadata(): Metadata
getWorksheetNames(): array
```

Additionally, the `XlsxReader` instance provides methods for
[configuration](#configuration)
and to
[retrieve performance information](#performance-considerations).

### `read()`: Reading a workbook using generators

Reads a workbook by returning generators that allow to walk through a workbook
without having to hold it entirely in memory,
effectively iterating through the workbook row by row.

This uses less memory on larger files than
[`XlsxReader::readAsArray()`](#readasarray-reading-a-workbook-into-an-array)
and
[`XlsxReader::readWithHeader()`](#readwithheader-reading-a-workbook-with-header-row-as-an-associative-array).
A larger file in this sense doesn't necessarily mean that the XLSX file size is larger.
It depends on other factors, especially the actual number of cells used,
the amount and length of different strings used.

Syntax:
```php
XlsxReader::read(): Generator
```

Example:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();
$reader->open(<pathToInputFile>);

foreach ($reader->read() as $worksheetName => $rows) {
    print $worksheetName . PHP_EOL;
    foreach ($rows as $rowIndex => $row) {
        print "\t" . $rowIndex . PHP_EOL;
        foreach ($row as $columnAddress => $column) {
            print "\t\t" . $columnAddress . ': ' . ($column instanceof DateTime ? $column->format('Y-m-d H:i:s') : $column) . "\n";
        }
    }
}
```

### `readAsArray()`: Reading a workbook into an array

Reads a workbook entirely and returns the contents as an array.
The array contains an entry for each worksheet.

Each worksheet contains an array for each row, indexed by the row number (1-based).

Each row contains an array with the row's columns,
indexed by either the cell address (default) or the column index.
This can be controlled using the option `Configuration::USE_CELL_ADDRESS`.

This will use more memory on larger files than
[`XlsxReader::read()`](#read-reading-a-workbook-using-generators)
but might be easier to work with.

Syntax:
```php
XlsxReader::readAsArray(): array
```

Example:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();
$reader->open(<pathToInputFile>);

// using cell address notation
$data = $reader->useCellAddress()->readAsArray();
// $data[<worksheetname (string)>][<rowindex (int)>][<celladdress (string)>]

// using 1-based column index notation 
$data = $reader->useColumnIndex()->readAsArray();
// $data[<worksheetname (string)>][<rowindex (int)>][<columnindex (int)>]

// reading just a single worksheet's data
$sheet1 = $reader->worksheets(['sheet1'])->readAsArray();
// $data['sheet1'][<rowindex (int)>][<celladdress (string)>]
```

### `readWithHeader()`: Reading a workbook with header row as an associative array

By default, the first available row of each worksheet is treated as containing headers.
Alternatively, the row that should be used as a header row can be specified as a parameter,
either globally or per worksheet.

The header row itself will not be returned.
The headers itself can be retrieved afterwards with
[`getHeaders()`](#getheaders-retrieve-headers).

Each returned row will be an associative array where columns are not indexed by the cell address (e.g. `A3`)
but by the value of the header row's column instead.

This will use more memory on larger files than
[`XlsxReader::read()`](#read-reading-a-workbook-using-generators)
but might be easier to work with.

Syntax:
```php
XlsxReader::readWithHeader(int|array $headerRowIndex = 0): array
```

Use first available row as header for all worksheets:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->readWithHeader();

// $data[<worksheetname (string)>][<rowindex (int)>][<header (string)>]
```

Use third row as header for all worksheets:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->readWithHeader(3);

// $data[<worksheetname (string)>][<rowindex (int)>][<header (string)>]
```

Use different header rows per worksheet:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->readWithHeader(['Sheet1' => 1, 'Sheet2' => 3]);
    // other existing worksheets will use the first available row as header

// $data[<worksheetname (string)>][<rowindex (int)>][<header (string)>]
```

The default for `$headerRowIndex` is `0`, i.e. the first available row is used as header.

#### `getHeaders()`: Retrieve headers

To retrieve the headers after reading the file, use `getHeaders()`:

Syntax:
```php
XlsxReader::getHeaders(string $worksheetName = ''): array
```

Example:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();
$reader->open(<pathToInputFile>)->readWithHeader();

// headers for all worksheets
$headers = $reader->getHeaders();
// $headers[<worksheetname (string)>][<columnindex (int)>] = [<header (string)>]

// headers for a specific worksheet
$headersSheet1 = $reader->getHeaders('Sheet1');
// $headersSheet1[<columnindex (int)>] = [<header (string)>]
```

### `getWorksheetNames()`: Retrieve the worksheet names of a workbook

The names of the worksheets present in a workbook can be retrieved using
`XlsxReader::getWorksheetNames()`.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();
$reader->open(<pathToInputFile>);

$worksheetNames = $reader->getWorksheetNames();
// e.g. ['sheet1', 'sheet2', 'sheet3']
```

### `getMetadata()`: Retrieve workbook metadata

```php
use SaschaKliche\PhpXlsxReader\Model\Metadata;
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$metadata = $reader
    ->open(<pathToInputFile>)
    ->getMetadata();                    // Metadata instance

$metadata->has(<property_name>);        // bool
$metadata->get(<property_name>);        // string|null
```

The available metadata properties are:
```php
Metadata::CREATED            // date/time when the file was created
Metadata::CREATOR            // name of the person who created the file
Metadata::LAST_MODIFIED_BY   // date/time when the file was last modified
Metadata::MODIFIED           // name of the person who last modified the file
```

`CREATOR` and `MODIFIED` are date/time strings in the form,
`Y-m-d\\TH:i:sp`, e.g. `2026-03-24T17:17:24Z`.

## Configuration

The library provides several options to configure.
These options can be set when instantiating the `XlsxReader` class ...

```php
use SaschaKliche\PhpXlsxReader\Configuration;
use SaschaKliche\PhpXlsxReader\XlsxReader;

// default configuration
$configuration = [
    Configuration::COLUMNS_TO_LOAD => [],
    Configuration::CUSTOM_FORMATS => [],
    Configuration::RETURN_CELL_OBJECTS => false,
    Configuration::READ_FORMULAS => false, // only if Configuration::RETURN_CELL_OBJECTS set to true
    Configuration::READ_HYPERLINKS => false, // only if Configuration::RETURN_CELL_OBJECTS set to true
    Configuration::ROWS_TO_LOAD => [],
    Configuration::SKIP_MISSING_CELLS => true,
    Configuration::SKIP_MISSING_ROWS => true,
    Configuration::USE_CELL_ADDRESS => true,
    Configuration::USE_DATE_SYSTEM_1900 => false,
    Configuration::WORKSHEETS_TO_LOAD => [],
];

$reader = new XlsxReader(<pathToInputFile>, $configuration);
```

... or by using the following fluent helper methods on an `XlsxReader` instance:

- `columns()` (default: `[]`)
- `customFormats()` (default: `[]`)
- `includeMissingCells()`
- `skipMissingCells()` (default)
- `includeMissingRows()`
- `skipMissingRows()` (default)
- `returnCellObjects(bool $readFormulas = false, bool $readHyperlinks = false)`
- `rows()` (default: `[]`)
- `useCellAddress()` (default)
- `useColumnIndex()`
- `useDateSystem1900()` (default)
- `useDateSystem1904()`
- `worksheets()` (default: `[]`)

### `customFormats()`: Apply custom formatting

By default date/time values are returned as DateTime objects and
numbers as integer or float, no formatting is applied.

Formatting can be applied by supplying an array of closures that
are called depending on the format ID or format string associated with
the number format of the cell.

> [!CAUTION]
> Format strings displayed in spreadsheet applications are usually localized
> but stored in US English format in the files.
> The library expects format strings as they appear in the file.
> E.g. the format `TT.MM.JJ hh:mm:ss` in a German UI would be stored as 
> `dd/mm/yy\ hh:mm:ss` in the file.

The closure receives the following arguments:
```php
function (mixed $value, string $rawValue, string $cellAddress, string $worksheetName)
```
- `$rawValue` contains the raw string from the cell value that has been read from the file.
- `$value` contains the value that would be returned by default from the library,
i.e. int/float/string/DateTime.
- `$cellAddress` contains the cell address (e.g. `'A7'`).
- `$worksheetName` contains the name of the worksheet (e.g. `'Sheet1'`).

The default format IDs can be found in the array `Styles::BUILTIN_FORMATS`.

The default is an empty array (`[]`).

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();
$reader->open(<pathToInputFile>);

$data = $reader->customFormats([
    // <id> => static fn(mixed $value, string $rawValue) => $value,
    '9' => static fn(int $number) => number_format($number * 100, 2) . '%',
    '14' => static fn(DateTime $date) => $date->format('d.m.Y'),
    // format string
    // '0%' => static fn(int $number) => number_format($number * 100, 2) . '%', // same as index '9'
    '#,##0.00' => static fn(float $number) => number_format($number, 2),
])->readAsArray();

// values for cells with number format 14 ('mm-dd-yy')
// will not be returned as DateTime objects but
// as formatted date strings  
```

### `includeMissingCells()`: Include missing cells

The equivalent of setting the option `Configuration::IGNORE_MISSING_CELLS` to `true`.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->includeMissingCells()
    ->readAsArray();
```

### `skipMissingCells()`: Ignore missing cells

The equivalent of setting the option `Configuration::IGNORE_MISSING_CELLS` to `true`.

This is the default configuration.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>);
    ->skipMissingCells()
    ->readAsArray();
```

### `includeMissingRows()`: Include missing rows

The equivalent of setting the option `Configuration::IGNORE_MISSING_ROWS` to `true`.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->includeMissingRows()
    ->readAsArray();
```

### `skipMissingRows()`: Ignore missing rows

The equivalent of setting the option `Configuration::IGNORE_MISSING_ROWS` to `true`.

This is the default configuration.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->skipMissingRows()
    ->readAsArray();
```

### `returnCellObjects()`: Return Cell object instances instead of cell values

The equivalent of setting the option `Configuration::RETURN_CELL_OBJECTS` to `true`.

If this is set to `true` (default is `false`) an instance of the `Cell` class will
be returned for each cell instead of the cell's value
(`int`/`float`/`string`/`DateTime` or value formatted by a `customFormats` closure).

Optionally, formulas and hyperlinks can be read when `Cell` objects are returned.
Hyperlinks have a performance impact because
they require additional read operations on the XLSX file contents.
That performance impact might be noticable on very large worksheets.
Reading formulas and hyperlinks is disabled by default.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();
$reader->open(<pathToInputFile>);

// don't read formulas and hyperlinks
$data = $reader->returnCellObjects()->readAsArray();

// read formulas and hyperlinks
$data = $reader->returnCellObjects(readFormulas: true, readHyperlinks: true)->readAsArray();

// $data[<worksheetname (string)>][<rowindex (int)>][<celladdress (string)>][<object (Cell)>]
```

See "[Cell objects](#cell-objects)" for details about the `Cell` objects.

### `useCellAddress()`: Indexing cells by cell address

The equivalent of setting the option `Configuration::USE_CELL_ADDRESS` to `true`.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->useCellAddress()
    ->readAsArray();

// $data[<worksheetname (string)>][<rowindex (int)>][<celladdress (string)>][<value (int|float|string|DateTime)>]
```

### `useColumnIndex()`: Indexing cells by column index number

The equivalent of setting the option `Configuration::USE_CELL_ADDRESS` to `false`.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->useCellAddress()
    ->readAsArray();

// $data[<worksheetname (string)>][<rowindex (int)>][<columnindex (int)>][<value (int|float|string|DateTime)>]
```

### `useDateSystem1900()`: Use 1900 date system

The equivalent of setting the option `Configuration::USE_DATE_SYSTEM_1900` to `true`.

The date system used by the XLSX file should be detected automatically.
If date values are not calculate correctly, try setting it explicitly.

This is the default configuration.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->useDateSystem1900()
    ->readAsArray();
```

### `useDateSystem1904()`: Use 1904 date system

The equivalent of setting the option `Configuration::USE_DATE_SYSTEM_1900` to `false`.

The date system used by the XLSX file should be detected automatically.
If date values are not calculate correctly, try setting it explicitly.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->useDateSystem1904()
    ->readAsArray();
```

### `columns()`: Selecting the columns to load from the worksheets

The equivalent of setting the option `Configuration::COLUMNS_TO_LOAD`.

If only specific rows from a worksheet are needed,
those rows can be requested using `columns()` by
providing an array of row index numbers.

Columns can either be requested "globally" for each existing worksheet ...

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->columns([2, 3, 4, 5])
    ->readAsArray();
```

... or explicitly for a specific worksheet ...

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->columns(['Sheet1' => [2, 3, 4, 5]])
    ->readAsArray();
```

The columns can be listed individually as shown above or providing `min` and/or `max` values:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();
$reader->open(<pathToInputFile>);

$reader->columns([Configuration::MIN => 3])->readAsArray();
$reader->columns(['Sheet1' => [Configuration::MIN => 3]])->readAsArray();

$reader->columns([Configuration::MIN => 2, Configuration::MAX => 5])->readAsArray();
// same as $reader->columns([2, 3, 4, 5])->readAsArray();
$reader->columns(['Sheet1' => [Configuration::MIN => 2, Configuration::MAX => 5]])->readAsArray();
// same as $reader->columns(['Sheet1' => [2, 3, 4, 5]])->readAsArray();
```

By default, non existing columns will not be returned even if they are requested.
`includeMissingColumns()` can be used to have them returned
as long as they are not beyond the last existing column on the worksheet.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->includeMissingCells()
    ->columns(['Sheet1' => [2, 3, 4, 5]])
    ->readAsArray();
```

> [!CAUTION]
> Columns that have been requested beyond the last existing column on a worksheet will not be returned!
> E.g. a worksheet has columns 1 - 5, requesting column 6 will not return a column 6.

The default is an empty array (`[]`) meaning all existing columns are loaded.

### `rows()`: Selecting the rows to load from the worksheets

The equivalent of setting the option `Configuration::ROWS_TO_LOAD`.

If only specific rows from a worksheet are needed,
those rows can be requested using `rows()` by
providing an array of row index numbers.

Rows can either be requested "globally" for each existing worksheet ...

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->rows([2, 3, 4, 5])
    ->readAsArray();
```

... or explicitly for a specific worksheet ...

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->rows(['Sheet1' => [2, 3, 4, 5]])
    ->readAsArray();
```

By default, non existing rows will not be returned even if they are requested.
`includeMissingRows()` can be used to have them returned
as long as they are not beyond the last existing row on the worksheet. 

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->includeMissingRows()
    ->rows(['Sheet1' => [2, 3, 4, 5]])
    ->readAsArray();
```

> [!CAUTION]
> Rows that have been requested beyond the last existing row on a worksheet will not be returned!
> E.g. a worksheet has rows 1 - 5, requesting row 6 will not return a row 6.

The default is an empty array (`[]`) meaning all existing rows are loaded.

### `worksheets()`: Selecting the worksheets to load from a workbook

The equivalent of setting the option `Configuration::WORKSHEETS_TO_LOAD`.

The default is an empty array (`[]`) meaning all worksheets are loaded.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$sheet1 = $reader
    ->open(<pathToInputFile>)
    ->worksheets(['sheet1', 'sheet2'])
    ->readAsArray();

// $data['sheet1'][<rowindex (int)>][<celladdress (string)>]
// $data['sheet2'][<rowindex (int)>][<celladdress (string)>]
```

## Cell objects

If the option `Configuration::RETURN_CELL_OBJECTS` is set to `true`, 

The `Cell` class provides the following methods:

```php
__toString(): string
getAddress(): string
getCellFormatString(): string
getColumnIndex(): int
getDataType(): int
getFormula(): string|null
getHyperlinkTarget(): string|null
getRawValue(): string
getRowIndex(): int
getValue(): string
```

### `Cell::getRawValue()`

The raw value returned by `getRawValue()` is the value read from the XLSX file, it is always a string.

For cells containing strings this will return a string with the index number of the shared strings file entry.
E.g. `'7'`.

For cells containing numbers this will return a string with the numeric representation which can be in exponential notation.
E.g. `'3.14'` or `'-42'`.

For cells containing date/time values this will return a string with a numeric value based on the date system of the file.
E.g. `'46113'` for 2026-04-01 00:00:00 or `'27106.801655092593'` for 1974-03-18 19:14:23.

### `Cell::getValue()`

The value returned by `getValue()` is either an `int`/`float`/`string`/`DateTime` or
a custom value returned by a formatting closure provided by `customFormats`. 

### `Cell::getCellFormatString()`

`getCellFormatString()` returns the format string associated with the cell.
E.g. `0%` for cells containing numeric values in percentage notation or
`#,##0.00` for numeric values with thousands separators and two decimals.

> [!CAUTION]
> Format strings displayed in spreadsheet applications are usually localized
> but stored in US English format in the files.
> `Cell::getCellFormatString()` returns format strings as they appear in the file.
> E.g. the format `TT.MM.JJ hh:mm:ss` in a German UI would be stored as
> `dd/mm/yy\ hh:mm:ss` in the file and returned in that format by the library.

### `Cell::getFormula()`

Formulas are only read if `readFormulas` is set to `true` on 
[`returnCellObjects()`](#returncellobjects-return-cell-object-instances-instead-of-cell-values)
or directly via `Configuration::READ_FORMULAS`.

`getFormula()` returns the cell's formula or null if the cell doesn't have a formula.
E.g. `'SUM(A2:C2)'` or `'SUBTOTAL(101,Tabelle1[Column A])'`.

### `Cell::getHyperlinkTarget()`

Formulas are only read if `readHyperlinks` is set to `true` on 
[`returnCellObjects()`](#returncellobjects-return-cell-object-instances-instead-of-cell-values)
or directly via `Configuration::READ_HYPERLINKS`.

`getHyperlinkTarget()` returns the hyperlink's target or null if the cell doesn't have a hyperlink.
E.g. `'https://github.com/'`.

### `Cell::getAddress()`, `Cell::getColumnIndex()`, `Cell::getRowIndex()`

`getAddress()` returns the cell's address, e.g. `C3`.
`getRowIndex()` and `getColumnIndex()` return the 1-based numeric index,
e.g. for cell `D3` the method `getRowIndex()` returns 3 and `getColumnIndex()` returns 4.
Both address and index numbers are always provided, there is no need to configure
`Configuration::USE_CELL_ADDRESS`.

### `Cell::getDataType()`

The `getDataType()` method returns one of the following values
depending on the type of data that has been detected:
```php
Cell::DATA_TYPE_UNKNOWN = 0
Cell::DATA_TYPE_DATETIME = 1
Cell::DATA_TYPE_FLOAT = 2
Cell::DATA_TYPE_INTEGER = 3
Cell::DATA_TYPE_STRING = 4
```

## Performance considerations

Large files can have a serious impact on both runtime duration and memory usage.
Files containing a lot of (different) text will use a lot of memory.
The text is stored in a shared strings files within the XLSX file and
is read into memory completely to be able to return the text values.
Files containing a small amount of (different) text will use significantly less memory.

By default all options that could have a performance impact are disabled, e.g. reading hyperlinks.

The duration and memory usage are recorded by default and can be retrieved
from the `XlsxReader` instance:

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();

$data = $reader
    ->open(<pathToInputFile>)
    ->readWithHeader();

$duration = $reader->getDurationInSeconds();   // float
$memory = $reader->getMemoryUsage();           // int
$peak = $reader->getMemoryPeakUsage();         // int

$reader->printPerformanceData();
// example output:
// Duration: 0.000343458 seconds
// Memory: 14.17 MiB
// Peak memory: 14.19 MiB
```

## Limitations

The library is pretty "dumb" to keep it small and simple.
Therefore a lot of information is currently ignored / not retrieved
and some features are not supported. Examples:

- Comments
- Conditional formatting
- Column spans are ignored, i.e. retrieved columns might contain a `null` value
- Complex / non-standard file layouts, e.g. multiple shared string files
- Defined names
- Embedded documents
- Encrypted files
- Filters
- Fixed rows and columns
- Images, pictures, charts, cell styles
- Information about fonts, alignment, borders, indentation, column width / row height, and colors
- Macro code
- Page layout like print areas, headers, footers
- Pivot charts and tables
- Revisions / tracked changes
- Sorting
- Validation

> [!CAUTION]
> The library might not be able to correctly read files using these features. 

## References

- [Office Implementation Information for ISO/IEC 29500 Standards Support](https://learn.microsoft.com/en-us/openspecs/office_standards/ms-oi29500/1fd4a662-8623-49c0-82f0-18fa91b413b8)
- [ECMA-376 Fifth Edition, Part 1](https://www.ecma-international.org/publications-and-standards/standards/ecma-376/)
