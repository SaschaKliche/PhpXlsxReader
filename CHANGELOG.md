# Release Notes

## v1.5.0 - 2026-11-11

- Added parameter `$withColumnIndex` to configuration helper `column()` to support returning rows with a single requested column without column index,
  i.e. each row only contains the single column's value
- Fixed a lot of documentation errors and generally improved the documentation
- `worksheet()` and `worksheets()` configuration helper methods support 1-based index numbers in addition to worksheet names
- Added `Configuration::USE_WORKSHEET_NAMES` to control if the results should be indexed by worksheet names (default) or 1-based worksheet index numbers

## v1.4.2 - 2026-10-18

- `Metadata::get()` returns the all metadata values as an array if called without parameters
- `Configuration::get()` returns the current configuration as an array if called without parameters
- Added `worksheet()` configuration helper method to load a single worksheet
- Added static `XlsxReader::from()` to open a file without having to instantiate the class first

## v1.4.1 - 2026-10-04

- Fix documentation
- Add hyperlinks to overview of configuration helper methods
- Add missing entry to v1.4.0 changelog

## v1.4.0 - 2026-10-04

- Fix: min/max configuration is broken in multiple ways
- Add cell() and row() configuration helpers
- Support lower case columns in configuration `column()` and `columns()` helpers

## v1.3.0 - 2026-04-28

- `XlsxReader::getHeaders()` accepts parameter `worksheet` as worksheet name (string) or index (int)

## v1.2.0 - 2026-04-19

- `XlsxReader::readWithHeader()` in combination with `includeMissingCells()` will make sure that
  a cell for each header exists in each row.

## v1.1.0 - 2026-04-16

- Added `XlsxReader::getWorksheetName()` to retrieve the name of a single worksheet
- Added `XlsxReader::getHeaders()` to retrieve the headers after using `XlsxReader::readWithHeader()`.
  This is important because the array keys of the first row might not contain all headers if columns are missing.
- `XlsxReader::open()` returns the current instance to allow method chaining.

## v1.0.0 - 2026-04-13

Initial release
