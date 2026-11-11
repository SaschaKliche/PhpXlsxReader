# XlsxReader

This library provides a simple interface to read Office Open XML (OOXML) spreadsheet files (XLSX).

## Main features
- Read the whole file into an [array](#readasarray-reading-a-workbook-into-an-array) 
- Read a file [row by row](#read-reading-a-workbook-using-generators)
  to reduce memory consumption
- Read a file containing header rows into an [associative array](#readwithheader-reading-a-workbook-with-header-row-as-an-associative-array)
  (think CSV)
- Read only specific [worksheets](#worksheets-selecting-the-worksheets-to-load-from-a-workbook),
  [rows](#rows-selecting-the-rows-to-load-from-the-worksheets),
  [columns](#columns-selecting-the-columns-to-load-from-the-worksheets)
- [Include](#includemissingrows-include-missing-rows)
  or [skip](#skipmissingrows-ignore-missing-rows)
  missing rows
- [Include](#includemissingcells-include-missing-cells)
  or [skip](#skipmissingcells-ignore-missing-cells)
  missing columns
- Read only the cell values or additional information like raw values, formulas, hyperlinks, and format strings
  by using [`Cell` objects](#returncellobjects-return-cell-object-instances-instead-of-cell-values)
- Cell values are returned as string, integer, float, or DateTime by default, based on the cell contents
- Use [custom formatter closures](#customformats-apply-custom-formatting) to automatically format the cell values
- Use [cell addresses](#usecelladdress-indexing-cells-by-cell-address)
  (e.g. `E17`)
  or [column indexes](#usecolumnindex-indexing-cells-by-column-index-number)
  (e.g. `5` for column `E`)
- Retrieve file [metadata](#getmetadata-retrieve-workbook-metadata) without having to read the whole file
- Retrieve [worksheet names](#getworksheetnames-retrieve-the-worksheet-names-of-a-workbook) without having to read the whole file
- [Configuration](#configuration) via array or fluent helper methods

> [!NOTE]
> This library is not optimized to handle large files. It might use excessive memory and CPU time.
> See [performance considerations](#performance-considerations) for further information.

> [!IMPORTANT]
> This library is not hardened against malformed files, this could create security issues.

## License

This library is licensed under the [MIT license](https://opensource.org/license/mit).
See the `LICENSE` file for details.

## Requirements

This library requires at least PHP 8.3 with the following extensions:
* `ctype`
* `xmlreader`
* `zip`

PHP 8.3, 8.4, and 8.5 are supported.

## Installation via Composer

Installation is quick & easy with [Composer](https://getcomposer.org/):

```bash
composer require saschakliche/phpxlsxreader
```

## Overview

By default, this library reads all worksheets in a given file,
returning each available cell for each available row.
Available means that the row / cell does exist in the input file.
Rows or cells that do not exist in the input file will not be returned at all by default.
That means that the data returned can contain non-consecutive rows / cells.
All of this can be configured to control what is being loaded / returned.

## `XlsxReader` API

The following methods are provided by the main class, `XlsxReader`:
```php
// opening a file
open(string $filePath): XlsxReader
// or as a static method
XlsxReader::from(string $filePath, array $configuration = []): XlsxReader

// reading the actual data
read(): Generator
readAsArray(): array
readWithHeader(int|array $headerRowIndex = 0): array

// retrieving information about the workbook
getHeaders(int|string $worksheet = ''): array
getMetadata(): Metadata
getWorksheetName(int $worksheetIndex): string
getWorksheetNames(): array
```

Additionally, the `XlsxReader` instance provides methods for
[configuration](#configuration)
and to
[retrieve performance information](#performance-considerations).

### Obtaining an `XlsxReader` instance

Without opening any file ...

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = new XlsxReader();
```

... or with opening a file ...

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = XlsxReader::from(<pathToInputFile>);
```

Opening the file doesn't read any data from the worksheets.
It determines the date format used and retrieves the names and internal IDs of the worksheets.

### `read()`: Reading a workbook using generators

Reads a workbook by returning generators that allow to walk through a workbook
without having to hold it entirely in memory,
effectively iterating through the workbook row by row.

This uses less memory on larger files than
[`readAsArray()`](#readasarray-reading-a-workbook-into-an-array)
and
[`readWithHeader()`](#readwithheader-reading-a-workbook-with-header-row-as-an-associative-array).
A larger file in this sense doesn't necessarily mean that the XLSX file size is large.
It depends on other factors, especially the actual number of cells used and
the amount and length of different strings used.

Syntax:
```php
read(): Generator
```

Example:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = XlsxReader::from(<pathToInputFile>);

foreach ($reader->read() as $worksheetName => $rows) {
    print 'Worksheet ' . $worksheetName . PHP_EOL;
    foreach ($rows as $rowIndex => $row) {
        print "\t" . 'Row ' . $rowIndex . PHP_EOL;
        foreach ($row as $cellAddress => $cell) {
            // $cell can be int/float/string/DateTime unless a custom formatter is used
            print "\t\t" . 'Cell ' . $cellAddress . ': ' . ($cell instanceof \DateTime ? $cell->format('Y-m-d H:i:s') : $cell) . "\n";
        }
    }
}
```

### `readAsArray()`: Reading a workbook into an array

Reads a workbook entirely and returns the contents as an array.  
The array contains an entry for each worksheet.  
Each worksheet contains an array for each row, indexed by the 1-based row number.  
Each row contains an array with the row's columns,
indexed by either the cell address (default) or the 1-based column index.
This can be controlled using the option `Configuration::USE_CELL_ADDRESS`.

This will use more memory on larger files than
[`read()`](#read-reading-a-workbook-using-generators)
but might be easier to work with.

Syntax:
```php
readAsArray(): array
```

Example:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = XlsxReader::from(<pathToInputFile>);

// using cell address notation
// $data[<worksheetname (string)>][<rowindex (int)>][<celladdress (string)>]
$data = $reader
    ->useCellAddress()
    ->readAsArray();

// using 1-based column index notation 
// $data[<worksheetname (string)>][<rowindex (int)>][<columnindex (int)>]
$data = $reader
    ->useColumnIndex()
    ->readAsArray();

// reading selected worksheets' data
// $data['Sheet1'][<rowindex (int)>][<celladdress (string)>]
// $data['Sheet2'][<rowindex (int)>][<celladdress (string)>]
$data = $reader
    ->worksheets(['Sheet1', 'Sheet2'])
    ->readAsArray();

// reading just a single worksheet's data
// $data['Sheet1'][<rowindex (int)>][<celladdress (string)>]
$data = $reader
    ->worksheet('Sheet1')
    ->readAsArray();
```

### `readWithHeader()`: Reading a workbook with header row as an associative array

By default, the first available row of each worksheet is treated as containing column headers.
Alternatively, the row that should be used as a column header row can be specified as a parameter,
either globally or per worksheet.

The column header row itself will not be returned.
The column headers can be retrieved afterwards with
[`getHeaders()`](#getheaders-retrieve-column-header-values).

Each returned row will be an associative array where columns are not indexed by the cell address (e.g. `A3`)
but by the value of the column header row's column value instead.

This will use more memory on larger files than
[`read()`](#read-reading-a-workbook-using-generators)
but might be easier to work with.

Syntax:
```php
readWithHeader(int|array $headerRowIndex = 0): array
```

Use the first available row as column headers for all worksheets (default):
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// $data[<worksheetname (string)>][<rowindex (int)>][<column header (string)>]
$data = XlsxReader::from(<pathToInputFile>)
    ->readWithHeader();
```

Use the third row as column headers for all worksheets:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// $data[<worksheetname (string)>][<rowindex (int)>][<column header (string)>]
$data = XlsxReader::from(<pathToInputFile>)
    ->readWithHeader(3);
```

Use different column header rows per worksheet:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// $data[<worksheetname (string)>][<rowindex (int)>][<column header (string)>]
$data = XlsxReader::from(<pathToInputFile>)
    // All other existing worksheets will use the first available row as column headers
    ->readWithHeader(['Sheet1' => 5, 'Sheet2' => 3]);
```

The default for `$headerRowIndex` is `0`, i.e. the first available row is used as header.

`includeMissingCells()` can be used
to make sure a cell is returned for each header for each row
even if a cell for that column does not exist in a row in the input file.
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// $data[<worksheetname (string)>][<rowindex (int)>][<column header (string)>]
$data = XlsxReader::from(<pathToInputFile>)
    ->includeMissingCells()
    ->readWithHeader();
```

### `getHeaders()`: Retrieve column header values

To retrieve the headers after reading the file, use `getHeaders()`.

Syntax:
```php
getHeaders(int|string $worksheet = ''): array
```

Example:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = XlsxReader::from(<pathToInputFile>)
    ->readWithHeader();

// headers for all worksheets
// $headers[<worksheetname (string)>][<columnindex (int)>, <column header (string)>]
$headers = $reader
    ->getHeaders();

// headers for a specific worksheet identified by the worksheet name
// $headers[<columnindex (int)>, <column header (string)>]
$headers = $reader
    ->getHeaders('Sheet1');

// headers for a specific worksheet identified by the index number
// $headers[<columnindex (int)>, <column header (string)>]
$headers = $reader
    ->getHeaders(3);
```

### `getWorksheetNames()`: Retrieve the worksheet names of a workbook

Syntax:
```php
// string[]
getWorksheetNames(): array
```

The names of the worksheets present in a workbook can be retrieved using
`getWorksheetNames()`.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// e.g. ['Sheet1', 'Sheet2', 'Sheet3']
$worksheetNames = XlsxReader::from(<pathToInputFile>)
    ->getWorksheetNames();
```

Retrieving the worksheet names does not read any worksheet data.

> [!IMPORTANT]
> A file has to be opened before calling this method either by using
> `XlsxReader::from(<pathToInputFile>)` or `$reader->open(<pathToInputFile>)`.

### `getWorksheetName()`: Retrieve the name of a single worksheet

Syntax:
```php
// 1-based worksheet index
getWorksheetName(int $worksheetIndex): string
```

The name of a specific worksheet present in a workbook can be retrieved using
`getWorksheetName(int $worksheetIndex)`.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// e.g. 'Sheet2'
$worksheetName = XlsxReader::from(<pathToInputFile>)
    ->getWorksheetName(2);
```

Retrieving a worksheet name does not read any worksheet data.

> [!IMPORTANT]
> A file has to be opened before calling this method either by using
> `XlsxReader::from(<pathToInputFile>)` or `$reader->open(<pathToInputFile>)`.

### `getMetadata()`: Retrieve workbook metadata

Syntax:
```php
getMetadata(): Metadata
```

The file's metadata like creator or the date of the last modification can be retrieved using `getMetaData()`.

```php
use SaschaKliche\PhpXlsxReader\Model\Metadata;
use SaschaKliche\PhpXlsxReader\XlsxReader;

$metadata = XlsxReader::from(<pathToInputFile>)
    ->getMetadata();                                          // Metadata instance

$hasProperty = $metadata->has(Metadata::<property_name>);     // bool
$propertyValue = $metadata->get(Metadata::<property_name>);   // mixed, null if property not available
```

The available metadata properties are:
```php
Metadata::CREATED            // date/time when the file was created
Metadata::CREATOR            // name of the person who created the file
Metadata::LAST_MODIFIED_BY   // name of the person who last modified the file
Metadata::MODIFIED           // date/time when the file was last modified
```

`CREATOR` and `MODIFIED` are date/time strings in the form
`Y-m-d\\TH:i:sp`, e.g. `2026-03-24T17:17:24Z`.

All metadata values can be retrieved as an array indexed by the property names
by using `get()` without a parameter:
```php
$metadata = XlsxReader::from(<pathToInputFile>)
    ->getMetadata()
    ->get();

// $metadata[Metadata::CREATED]
// $metadata[Metadata::CREATOR]
// $metadata[Metadata::LAST_MODIFIED_BY]
// $metadata[Metadata::MODIFIED]
```

Retrieving the metadata does not read any worksheet data.

> [!IMPORTANT]
> A file has to be opened before calling this method either by using
> `XlsxReader::from(<pathToInputFile>)` or `$reader->open(<pathToInputFile>)`.

## Configuration

This library provides several options to configure.
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
    Configuration::SINGLE_COLUMN_ROWS_WITH_INDEX => true, // only effective if Configuration::COLUMNS_TO_LOAD specifies a single column
    Configuration::SKIP_MISSING_CELLS => true,
    Configuration::SKIP_MISSING_ROWS => true,
    Configuration::USE_CELL_ADDRESS => true,
    Configuration::USE_DATE_SYSTEM_1900 => false,
    Configuration::WORKSHEETS_TO_LOAD => [],
    Configuration::USE_WORKSHEET_NAMES => true,
];

$reader = new XlsxReader(<pathToInputFile>, $configuration);
```

... or by using the following fluent helper methods on an `XlsxReader` instance:

- [`column(int|string $column, bool $withColumnIndex = true): XlsxReader`](#column-selecting-a-single-column-to-load-from-the-worksheets)
- [`columns(array $columns): XlsxReader`](#columns-selecting-the-columns-to-load-from-the-worksheets)
- [`customFormats(array $formats): XlsxReader`](#customformats-apply-custom-formatting)
- [`includeMissingCells(): XlsxReader`](#includemissingcells-include-missing-cells): XlsxReader
- [`skipMissingCells(): XlsxReader`](#skipmissingcells-ignore-missing-cells) (default)
- [`includeMissingRows(): XlsxReader`](#includemissingrows-include-missing-rows)
- [`skipMissingRows(): XlsxReader`](#skipmissingrows-ignore-missing-rows) (default)
- [`returnCellObjects(bool $readFormulas = false, bool $readHyperlinks = false): XlsxReader`](#returncellobjects-return-cell-object-instances-instead-of-cell-values)
- [`row(int $row): XlsxReader`](#row-selecting-a-single-row-to-load-from-the-worksheets)
- [`rows(array $rows): XlsxReader`](#rows-selecting-the-rows-to-load-from-the-worksheets)
- [`useCellAddress(): XlsxReader`](#usecelladdress-indexing-cells-by-cell-address) (default)
- [`useColumnIndex(): XlsxReader`](#usecolumnindex-indexing-cells-by-column-index-number)
- [`useDateSystem1900(): XlsxReader`](#usedatesystem1900-use-1900-date-system) (default)
- [`useDateSystem1904(): XlsxReader`](#usedatesystem1904-use-1904-date-system)
- [`useWorksheetIndexNumbers()`](#useWorksheetIndexNumbers-indexing-the-results-by-worksheet-index-numbers)
- [`useWorksheetNames()`](#useWorksheetNames-indexing-the-results-by-worksheet-names)
- [`worksheet(string $worksheetName): XlsxReader`](#worksheet-selecting-a-single-worksheet-to-load-from-a-workbook)
- [`worksheets(array $worksheetNames): XlsxReader`](#worksheets-selecting-the-worksheets-to-load-from-a-workbook)

These configuration helper methods have to be used before calling methods that
retrieve data, i.e. before calling `read()` / `readAsArray()` / `readWithHeader()`.
The configuration helper methods are documented below.

### `column()`: Selecting a single column to load from the worksheets

Syntax:
```php
column(int|string $column, bool $withColumnIndex = true): XlsxReader
```

The equivalent of setting the option `Configuration::COLUMNS_TO_LOAD` to a single value.
The parameter `$withColumnIndex` is the equivalent of setting the option `Configuration::SINGLE_COLUMN_ROWS_WITH_INDEX`.

Loads data for the given column from all available worksheets.
If data should only be loaded from specific worksheets,
the [`worksheet()`](#worksheet-selecting-a-single-worksheet-to-load-from-a-workbook) /
[`worksheets()`](#worksheets-selecting-the-worksheets-to-load-from-a-workbook)
configuration helper methods can be used.

`$column` can be a 1-based index value (`1`, `2`, ...) or character (`A`, `B`, ...).

Using the 1-based column index:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->column(3)
    ->readAsArray();

/*
[
    'Sheet1' => [
        // each row is still an array indexed by the column index even though it only holds a single column
        1 => ['C1' => 'Column C'],
        2 => ['C2' => -42],
        3 => ['C3' => 3.14],
        ...
    ],
    ...
]
*/
```

Using the column character:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->column('C')
    ->readAsArray();
```

If `$withColumnIndex` is set to `false`, the resulting rows will only contain the value from the requested column:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->column(column: 'C', withColumnIndex: false)
    ->readAsArray();

/*
[
    'Sheet1' => [
        // each row is no longer an array, just the value of the single column
        1 => 'Column C',
        2 => -42,
        3 => 3.14,
        ...
    ],
    ...
]
*/
```

### `columns()`: Selecting the columns to load from the worksheets

Syntax:
```php
// int[]|string[]|mixed[] $columns
columns(array $columns): XlsxReader
```

The equivalent of setting the option `Configuration::COLUMNS_TO_LOAD`.

Loads data for the given columns from all available worksheets.
If data should only be loaded from specific worksheets,
the [`worksheet()`](#worksheet-selecting-a-single-worksheet-to-load-from-a-workbook) /
[`worksheets()`](#worksheets-selecting-the-worksheets-to-load-from-a-workbook)
configuration helper methods can be used.

`$columns` can be 1-based index values (`1`, `2`, ...) or characters (`A`, `B`, ...).

Specific columns can either be requested for each existing worksheet ...

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->columns([2, 3, 4, 5])
    ->readAsArray();
```

... or explicitly for specific worksheets ...

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    // For all other existing worksheets all columns will be returned!
    ->columns(['Sheet1' => [2, 3, 4, 5], 'Sheet2' => [8, 15]])
    ->readAsArray();
```

The columns can be listed individually as shown above or selected by providing `min` and/or `max` values:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = XlsxReader::from(<pathToInputFile>);

// for all worksheets
$data = $reader
    ->columns([Configuration::MIN => 3])
    ->readAsArray();

// start at column 3 on worksheet Sheet1; all other worksheets without restrictions
$data = $reader
    ->columns(['Sheet1' => [Configuration::MIN => 3]])
    ->readAsArray();

// same as $reader->columns([2, 3, 4, 5])->readAsArray();
$data = $reader
    ->columns([Configuration::MIN => 2, Configuration::MAX => 5])
    ->readAsArray();

// same as $reader->columns(['Sheet1' => [2, 3, 4, 5]])->readAsArray();
$data = $reader
    ->columns(['Sheet1' => [Configuration::MIN => 2, Configuration::MAX => 5]])
    ->readAsArray();
```

By default, non existing columns will not be returned even if they are requested.
The configuration helper method `includeMissingColumns()` can be used to have them returned
as long as they are not beyond the last existing column on the worksheet.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->includeMissingCells()
    ->columns([2, 3, 4, 5])
    ->readAsArray();
```

> [!CAUTION]
> Columns that have been requested beyond the last existing column in a row will not be returned!
> E.g. a row has columns 1 - 5, requesting column 6 will not return a column 6.  
> Exception: If a header row is being used
> ([`XlsxReader::readWithHeader()`](#readwithheader-reading-a-workbook-with-header-row-as-an-associative-array))
> a cell for each header will be returned for each row on the worksheet.

The default is an empty array (`[]`) meaning all existing columns are loaded.

### `customFormats()`: Apply custom formatting

Syntax:
```php
// array<int|string, \Closure>
customFormats(array $formats): XlsxReader
```

By default date/time values are returned as DateTime objects and
numbers as integer or float, no formatting is applied.

Formatting can be applied by supplying an array of closures that
are called depending on the format ID or format string associated with
the number format of the cell.
The default format IDs can be found in the array `Styles::BUILTIN_FORMATS`.

> [!CAUTION]
> Format strings displayed in spreadsheet applications are usually localized
> but stored in US English format in the files.
> This library expects format strings as they appear in the file.
> E.g. the format `TT.MM.JJ hh:mm:ss` in a German UI would be stored as 
> `dd/mm/yy\ hh:mm:ss` in the file.

The closure receives the following arguments:
```php
function (mixed $value, string $rawValue, string $cellAddress, string $worksheetName)
```
- `$value` contains the value that would be returned by default from this library,
i.e. an int / float / string / DateTime value.
- `$rawValue` contains the raw string from the cell value that has been read from the file.
- `$cellAddress` contains the cell address (e.g. `A7`).
- `$worksheetName` contains the name of the worksheet (e.g. `Sheet1`).

The default is an empty array (`[]`) meaning no custom formatting is applied.

Example:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->customFormats([
        // using a format ID
        // <id> => static fn(mixed $value, string $rawValue, string $cellAddress, string $worksheetName) => $value,
        '9' => static fn(int $number) => number_format($number * 100, 2) . '%',
        // Values for cells with number format 14 ('mm-dd-yy') will
        // not be returned as DateTime objects but as formatted date strings  
        '14' => static fn(DateTime $date) => $date->format('d.m.Y'),

        // using a format string
        // '0%' => static fn(int $number) => number_format($number * 100, 2) . '%', // same as index '9'
        '#,##0.00' => static fn(float $number) => number_format($number, 2),
    ])
    ->readAsArray();
```

### `includeMissingCells()`: Include missing cells

Syntax:
```php
includeMissingCells(): XlsxReader
```

The equivalent of setting the option `Configuration::SKIP_MISSING_CELLS` to `false`.

A missing cell is a cell for a column that is not stored in a worksheet's row but
other cells for columns after that are stored.  
Example:  
A worksheet has data for column 4 (= `D`) in the first row (= `D1`).
Without `includeMissingCells()` the returned row only contains the existing column `D`.
With `includeMissingCells()` the returned row also contains columns `A`, `B`, and `C` with a `null` value.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->includeMissingCells()
    ->readAsArray();
```

### `skipMissingCells()`: Ignore missing cells

Syntax:
```php
skipMissingCells(): XlsxReader
```

The equivalent of setting the option `Configuration::SKIP_MISSING_CELLS` to `true`.

This is the default configuration.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->skipMissingCells()
    ->readAsArray();
```

### `includeMissingRows()`: Include missing rows

Syntax:
```php
includeMissingRows(): XlsxReader
```

The equivalent of setting the option `Configuration::SKIP_MISSING_ROWS` to `false`.

A missing row is a row that is not stored in a worksheet but
other rows after that row are stored in the worksheet.  
Example:  
A worksheet has data for row 4 but not for rows 1 to 3.
Without `includeMissingRows()` the returned data only contains the existing row `4`.
With `includeMissingRows()` the returned data also contains rows `1`, `2`, and `3` with an empty array (`[]`) value.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->includeMissingRows()
    ->readAsArray();
```

### `skipMissingRows()`: Ignore missing rows

Syntax:
```php
skipMissingRows(): XlsxReader
```

The equivalent of setting the option `Configuration::SKIP_MISSING_ROWS` to `true`.

This is the default configuration.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->skipMissingRows()
    ->readAsArray();
```

### `returnCellObjects()`: Return Cell object instances instead of cell values

Syntax:
```php
returnCellObjects(): XlsxReader
```

The equivalent of setting the option `Configuration::RETURN_CELL_OBJECTS` to `true`.

If this is set to `true` (default is `false`) an instance of the `Cell` class will
be returned for each cell instead of the cell's value
(`int`/`float`/`string`/`DateTime` or value formatted by a `customFormats` closure).

Optionally, formulas and hyperlinks can be read when `Cell` objects are returned.
Hyperlinks have a performance impact because
they require additional read operations on the XLSX file contents.
That performance impact might be noticeable on very large worksheets.
Reading formulas and hyperlinks is disabled by default.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$reader = XlsxReader::from(<pathToInputFile>);

// don't read formulas and hyperlinks
$data = $reader
    ->returnCellObjects()
    ->readAsArray();

// read formulas and hyperlinks
$data = $reader
    ->returnCellObjects(readFormulas: true, readHyperlinks: true)
    ->readAsArray();

// $data[<worksheetname (string)>][<rowindex (int)>][<celladdress (string)>][<object (Cell)>]
```

See [Cell objects](#cell-objects) for details about the `Cell` objects' methods.

### `row()`: Selecting a single row to load from the worksheets

Syntax:
```php
// 1-based row index
row(int $row): XlsxReader
```

The equivalent of setting the option `Configuration::ROWS_TO_LOAD` to a single value.

Loads data for the given row from all available worksheets.
If data should only be loaded from specific worksheets,
the [`worksheet()`](#worksheet-selecting-a-single-worksheet-to-load-from-a-workbook) /
[`worksheets()`](#worksheets-selecting-the-worksheets-to-load-from-a-workbook)
configuration helper methods can be used.

Loading from all worksheets:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->row(4)
    ->readAsArray();
```

Loading from a single worksheet:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    // Only data from worksheet 'Sheet3'
    ->worksheet('Sheet3')
    ->row(4)
    ->readAsArray();
```

Loading from specific worksheets:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    // Only data from worksheets 'Sheet1 and 'Sheet3'
    ->worksheets(['Sheet1', 'Sheet3'])
    ->row(4)
    ->readAsArray();
```

### `rows()`: Selecting the rows to load from the worksheets

Syntax:
```php
// int[] containing 1-based row index
rows(array $rows): XlsxReader
```

The equivalent of setting the option `Configuration::ROWS_TO_LOAD`.

If only specific rows from a worksheet are needed,
those rows can be requested using `rows()` by
providing an array of 1-based row index numbers.

Rows can either be requested for each existing worksheet ...

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->rows([2, 3, 4, 5])
    ->readAsArray();
```

... or explicitly for a specific worksheet ...

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    // Only rows 2, 3, 4, 5 for worksheet 'Sheet1'.
    // All rows from all other worksheets!
    ->rows(['Sheet1' => [2, 3, 4, 5]])
    ->readAsArray();
```

... or only from a specific worksheet ...

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    // Only data from worksheet 'Sheet1'
    ->worksheet('Sheet1')
    // Only rows 2, 3, 4, 5 - no need to specify the worksheet name!
    ->rows([2, 3, 4, 5])
    ->readAsArray();
```

By default, non existing rows will not be returned even if they are requested.
The configuration helper method
`includeMissingRows()` can be used to have them returned
as long as they are not beyond the last existing row on the worksheet.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->includeMissingRows()
    ->rows([2, 3, 4, 5])
    ->readAsArray();
```

> [!CAUTION]
> Rows that have been requested beyond the last existing row on a worksheet will not be returned!
> E.g. a worksheet has rows 1 - 5, requesting row 6 will not return a row 6.

The default is an empty array (`[]`) meaning all existing rows are loaded.

### `useCellAddress()`: Indexing cells by cell address

Syntax:
```php
useCellAddress(): XlsxReader
```

The equivalent of setting the option `Configuration::USE_CELL_ADDRESS` to `true`.
This is the default.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// $data[<worksheetname (string)>][<rowindex (int)>][<celladdress (string)>][<value (int|float|string|DateTime)>]
$data = XlsxReader::from(<pathToInputFile>)
    ->useCellAddress()
    ->readAsArray();
```

### `useColumnIndex()`: Indexing cells by column index number

Syntax:
```php
useColumnIndex(): XlsxReader
```

The equivalent of setting the option `Configuration::USE_CELL_ADDRESS` to `false`.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// $data[<worksheetname (string)>][<rowindex (int)>][<columnindex (int)>][<value (int|float|string|DateTime)>]
$data = XlsxReader::from(<pathToInputFile>)
    ->useColumnIndex()
    ->readAsArray();
```

### `useDateSystem1900()`: Use 1900 date system

Syntax:
```php
useDateSystem1900(): XlsxReader
```

The equivalent of setting the option `Configuration::USE_DATE_SYSTEM_1900` to `true`.

This is the default configuration but
the date system used by the XLSX file should be detected automatically.
If date values are not calculated correctly, this method can be used to setting it explicitly.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->useDateSystem1900()
    ->readAsArray();
```

### `useDateSystem1904()`: Use 1904 date system

Syntax:
```php
useDateSystem1904(): XlsxReader
```

The equivalent of setting the option `Configuration::USE_DATE_SYSTEM_1900` to `false`.

The date system used by the XLSX file should be detected automatically.
If date values are not calculated correctly, this method can be used to setting it explicitly.

```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

$data = XlsxReader::from(<pathToInputFile>)
    ->useDateSystem1904()
    ->readAsArray();
```

### `useWorksheetIndexNumbers()`: Indexing the results by worksheet index numbers

Syntax:
```php
useWorksheetIndexNumbers(): XlsxReader
```

The equivalent of setting the option `Configuration::USE_WORKSHEET_NAMES` to `false`.

The returned data will be indexed by the 1-based worksheet index number:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// $data[<worksheet index number (int)>][<rowindex (int)>][<celladdress (string)>]
$data = XlsxReader::from(<pathToInputFile>)
    ->useWorksheetIndexNumbers()
    ->readAsArray();
```

### `useWorksheetNames()`: Indexing the results by worksheet names

Syntax:
```php
useWorksheetNames(): XlsxReader
```

The equivalent of setting the option `Configuration::USE_WORKSHEET_NAMES` to `true`.

This is the default configuration.

The returned data will be indexed by the worksheet name:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// $data[<worksheet name (string)>][<rowindex (int)>][<celladdress (string)>]
$data = XlsxReader::from(<pathToInputFile>)
    ->useWorksheetNames()
    ->readAsArray();
```

### `worksheet()`: Selecting a single worksheet to load from a workbook

Syntax:
```php
worksheet(string|int $worksheetName): XlsxReader
```

The equivalent of setting the option `Configuration::WORKSHEETS_TO_LOAD` to an array with a single value.

By default all worksheets are loaded.

Using the worksheet name:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// $data['Sheet1'][<rowindex (int)>][<celladdress (string)>]
$data = XlsxReader::from(<pathToInputFile>)
    ->worksheet('Sheet1')
    ->readAsArray();
```

Using a 1-based index number:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// Still uses the worksheet name as the index in the returned data!
// $data['Sheet1'][<rowindex (int)>][<celladdress (string)>]
$data = XlsxReader::from(<pathToInputFile>)
    ->worksheet(1)
    ->readAsArray();
```

Chaining this method with other `worksheet()` or `worksheets()` calls
(e.g. `->worksheet('Sheet1')->worksheet('Sheet2')`)
will replace the previously set value.
If data should be loaded from multiple specific worksheets,
the [`worksheets()`](#worksheets-selecting-the-worksheets-to-load-from-a-workbook)
configuration helper methods should be used and only used once.

### `worksheets()`: Selecting the worksheets to load from a workbook

Syntax:
```php
// string[]|int[] $worksheetNames
worksheets(array $worksheetNames): XlsxReader
```

The equivalent of setting the option `Configuration::WORKSHEETS_TO_LOAD`.

The default is an empty array (`[]`) meaning all worksheets are loaded.

Using worksheet names:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// $data['Sheet1'][<rowindex (int)>][<celladdress (string)>]
// $data['Sheet2'][<rowindex (int)>][<celladdress (string)>]
$data = XlsxReader::from(<pathToInputFile>)
    ->worksheets(['Sheet1', 'Sheet2'])
    ->readAsArray();
```

Using 1-based index numbers:
```php
use SaschaKliche\PhpXlsxReader\XlsxReader;

// Still uses the worksheet name as the index in the returned data!
// $data['Sheet1'][<rowindex (int)>][<celladdress (string)>]
// $data['Sheet2'][<rowindex (int)>][<celladdress (string)>]
$data = XlsxReader::from(<pathToInputFile>)
    ->worksheets([1, 2])
    ->readAsArray();
```

Chaining this method with other `worksheet()` or `worksheets()` calls
(e.g. `->worksheets(['Sheet1', 'Sheet2'])->worksheet('Sheet2')`)
will replace the previously set value.
If data should be loaded from multiple specific worksheets,
the [`worksheets()`](#worksheets-selecting-the-worksheets-to-load-from-a-workbook)
configuration helper methods should be used once.

## Cell objects

If the option `Configuration::RETURN_CELL_OBJECTS` is set to `true`,
e.g. by using the [`returnCellObjects()`](#returncellobjects-return-cell-object-instances-instead-of-cell-values)
configuration helper method,
instances of the class `Cell` will be returned instead of raw values.

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

### `Cell::__toString()`

Syntax:
```php
__toString(): string
```

`__toString()` returns a `string` representation of the cell's value regardless of the data type.
A `DateTime` value will be formatted, other values are casted to `string`.

### `Cell::getAddress()`

Syntax:
```php
getAddress(): string
```

`getAddress()` returns the cell's address, e.g. `C3`.
Both cell address and column/row index numbers are always provided when using `Cell` instances,
there is no need to configure
`Configuration::USE_CELL_ADDRESS`.

### `Cell::getCellFormatString()`

Syntax:
```php
getCellFormatString(): string
```

`getCellFormatString()` returns the format string associated with the cell.
E.g. `0%` for cells containing numeric values in percentage notation or
`#,##0.00` for numeric values with thousands separators and two decimals.

> [!CAUTION]
> Format strings displayed in spreadsheet applications are usually localized
> but stored in US English format in the files.
> `getCellFormatString()` returns format strings as they appear in the file.
> E.g. the format `TT.MM.JJ hh:mm:ss` in a German UI would be stored as
> `dd/mm/yy\ hh:mm:ss` in the file and returned in that format by this library.

### `Cell::getColumnIndex()` / `Cell::getRowIndex()`

Syntax:
```php
getColumnIndex(): int
getRowIndex(): int
```

`getColumnIndex()` and `getRowIndex()` return the 1-based numeric index.
For cell `D3` the method `getColumnIndex()` returns 4 and `getRowIndex()` returns 3.
Both cell address and column/row index numbers are always provided when using `Cell` instances,
there is no need to configure
`Configuration::USE_CELL_ADDRESS`.

### `Cell::getDataType()`

Syntax:
```php
getDataType(): int
```

The `getDataType()` method returns one of the following values
depending on the type of data that has been detected:
- `Cell::DATA_TYPE_UNKNOWN`
- `Cell::DATA_TYPE_DATETIME`
- `Cell::DATA_TYPE_FLOAT`
- `Cell::DATA_TYPE_INTEGER`
- `Cell::DATA_TYPE_STRING`

### `Cell::getFormula()`

Syntax:
```php
getFormula(): string|null
```

Formulas are only read if the parameter `readFormulas` is set to `true` on
[`returnCellObjects()`](#returncellobjects-return-cell-object-instances-instead-of-cell-values)
or directly via `Configuration::READ_FORMULAS`.

`getFormula()` returns the cell's formula or `null` if the cell doesn't have a formula.
E.g. `'SUM(A2:C2)'` or `'SUBTOTAL(101,Tabelle1[Column A])'`.

> [!NOTE]
> The formula string usually does not start with the `=` character but that cannot be guaranteed.

### `Cell::getHyperlinkTarget()`

Syntax:
```php
getHyperlinkTarget(): string|null
```

Hyperlinks are only read if the parameter `readHyperlinks` is set to `true` on
[`returnCellObjects()`](#returncellobjects-return-cell-object-instances-instead-of-cell-values)
or directly via `Configuration::READ_HYPERLINKS`.

`getHyperlinkTarget()` returns the hyperlink's target or `null` if the cell doesn't have a hyperlink.
E.g. `'https://github.com/'`.

### `Cell::getRawValue()`

Syntax:
```php
getRawValue(): string
```

The raw value returned by `getRawValue()` is the value read from the XLSX file, it is always a string.

For cells containing strings this will return a string with the index number of the shared strings file entry.
E.g. `7`.

For cells containing numbers this will return a string with the numeric representation which can be in exponential notation.
E.g. `3.14` or `-42`.

For cells containing date/time values this will return a string with a numeric value based on the date system of the file.
E.g. `46113` for 2026-04-01 00:00:00 or `27106.801655092593` for 1974-03-18 19:14:23.

### `Cell::getValue()`

Syntax:
```php
getValue(): mixed
```

The value returned by `getValue()` is either an `int`/`float`/`string`/`DateTime` based on the cell's data type or
a custom value returned by a formatting closure provided by
[`customFormats`](#customformats-apply-custom-formatting). 

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

$reader = XlsxReader::from(<pathToInputFile>);

$data = $reader
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

This library is pretty "dumb" to keep it small and simple.
Therefore a lot of information is currently ignored / not retrieved
and some features are not supported. Examples:

- Cell comments
- Column spans are ignored, i.e. retrieved columns might contain a `null` value
- Complex / non-standard file layouts, e.g. multiple shared string files
- Conditional formatting
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

> [!IMPORTANT]
> This library relies on the default file structure typically used in XLSX files.
> Please report any files that are not readable by this library.

> [!CAUTION]
> This library might not be able to correctly read files using these features. 

## References

- [Office Implementation Information for ISO/IEC 29500 Standards Support](https://learn.microsoft.com/en-us/openspecs/office_standards/ms-oi29500/1fd4a662-8623-49c0-82f0-18fa91b413b8)
- [ECMA-376 Fifth Edition, Part 1](https://www.ecma-international.org/publications-and-standards/standards/ecma-376/)
