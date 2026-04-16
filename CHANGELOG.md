# Release Notes

## v1.1.0 - 2026-04-16

- Added `XlsxReader::getWorksheetName()` to retrieve the name of a single worksheet
- Added `XlsxReader::getHeaders()` to retrieve the headers after using `XlsxReader::readWithHeader()`.
  This is important because the array keys of the first row might not contain all headers if columns are missing.
- `XlsxReader::open()` returns the current instance to allow method chaining.

## v1.0.0 - 2026-04-13

Initial release
