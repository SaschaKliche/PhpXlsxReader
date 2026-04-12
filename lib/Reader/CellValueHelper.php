<?php declare(strict_types=1);

namespace SaschaKliche\PhpXlsxReader\Reader;

use DateInterval;
use DateTime;
use Exception;

trait CellValueHelper
{
    protected const string FORMAT_STRING_GENERAL = 'General';

    protected const int FORMAT_TYPE_UNKNOWN = 0;
    protected const int FORMAT_TYPE_DATETIME = 1;
    protected const int FORMAT_TYPE_ELAPSED = 2;
    protected const int FORMAT_TYPE_NUMBER = 3;
    protected const int FORMAT_TYPE_TEXT = 4;

    /**
     * @throws Exception
     */
    protected function convertRawValue(mixed $rawValue, string $dataType, string $cellFormatString): mixed
    {
        if ($dataType === self::TYPE_VALUE_STRING) {
            return $rawValue;
        }

        // looks like dataType only ever contains 's' for shared strings
        // probably 18.18.11 ST_CellType (Cell Type)
        if ($dataType === self::TYPE_VALUE_SHARED_STRING) {
            return $this->sharedStrings->getSharedString($rawValue);
        }

        if ($cellFormatString !== '') {
            if ($this->determineFormatType($cellFormatString) === self::FORMAT_TYPE_DATETIME) {
                return $this->convertDateValue($rawValue);
            }
        }

        // ctype_digit() requires ext-ctype
        if (ctype_digit(ltrim($rawValue, '-'))) {
            return (int) $rawValue;
        }

        // Try to convert the string to a float
        $floatValue = (float) $rawValue;
        if ($floatValue && (intval($floatValue) != $floatValue)) {
            // We treat it as a float if the parsing succeeded and the value is not equivalent to an int
            return $floatValue;
        }

        if ($rawValue === '') {
            // we should not end up here but who knows
            return '';
        }

        return $rawValue;
    }

    protected function determineFormatType(string $format): int
    {
        if ($format === self::FORMAT_STRING_GENERAL) {
            return self::FORMAT_TYPE_UNKNOWN;
        }

        if (preg_match('#\[(h+)](?::(m+))?|\[(m+)](?::(s+))?|\[(s+)](?:\.(0+))?#', $format) === 1) {
            return self::FORMAT_TYPE_ELAPSED;
        }

        $zeroFound = false;
        $bracketOpen = false;
        $escaping = false;
        $singleCharEscaping = false;

        $formatType = null;
        foreach (str_split($format) as $letter) {
            if ($singleCharEscaping) {
                $singleCharEscaping = false;
                continue;
            }
            if ($escaping && $letter !== '"') {
                continue;
            }

            switch ($letter) {
                case 'd':
                case 'D':
                case 'y':
                case 'Y':
                    if ($bracketOpen) {
                        break;
                    }
                    $formatType = self::FORMAT_TYPE_DATETIME;
                    break;

                case 'h':
                case 'H':
                case 's':
                case 'S':
                case 'm':
                case 'M':
                    if ($bracketOpen) {
                        $formatType = self::FORMAT_TYPE_ELAPSED;
                    } else {
                        // date or time
                        $formatType = self::FORMAT_TYPE_DATETIME;
                    }
                    break;

                case '@':
                    $formatType = self::FORMAT_TYPE_TEXT;
                    break;

                case '0':
                    // could be a time (HUNDREDS), elapsed time (HUNDREDS), or a number
                    // even though we should have caught time and elapsed time earlier
                    // so most probably a number
                    $zeroFound = true;
                    break;

                case '[':
                    $bracketOpen = true;
                    break;
//                case '$':
//                    if ($bracketOpen) {
//                        // start of localized currency or System long date format or System time format
//                    }
//                    break;
                case ']':
                    $bracketOpen = false;
                    break;

                case '#':
                case '?':
                    $formatType = self::FORMAT_TYPE_NUMBER;
                    break;

                case '\\':
                    $singleCharEscaping = true;
                    break;

                case '"':
                    $escaping = !$escaping;
                    break;

                default:
                    break;
            }

            if ($formatType !== null) {
                return $formatType;
            }
        }

        if ($zeroFound) {
            return self::FORMAT_TYPE_NUMBER;
        }

        // default to text if we were unable to detect the format at all
        return self::FORMAT_TYPE_TEXT;
    }

    /**
     * @throws Exception
     */
    protected function convertDateValue(string $value): DateTime
    {
        $floatValue = (float) $value;
        $days = (int) floor($floatValue);
        $dayFraction = $floatValue - $days;

        if ($this->useDateSystem1900) {
            $date = new DateTime('1899-12-30 00:00:00'); // default date system 1899-12-30 12:00:00
        } else {
            $date = new DateTime('1904-01-01 00:00:00'); // 1904 date system
        }

        // 18.17.4.1 Date Conversion for Serial Date-Times
        try {
            $duration = 'P' . abs($days) . 'D';
            $interval = new DateInterval($duration);
        } catch (Exception) {
            throw new Exception('Invalid duration "' . $duration . '"');
        }
        if ($days < 0) {
            $interval->invert = 1;
        }
        $date->add($interval);
        if ($dayFraction == 0) {
            return $date;
        }

        // 18.17.4.2 Time Conversion for Serial Date-Times
        // Values from 0–0.99999999 represent times from the starting instant 0:00:00 (12:00:00 AM)
        // to the last instant 23:59:59 (11:59:59 P.M.).
        // Going forward in time, the time component of a serial date-time increases by 1/86,400 each second.
        // [Note: As such, the time 12:00 has a serial date-time time component of 0.5. end note]
        $time = $dayFraction * 86400;
        $hours = (int) floor($time / 60 / 60);
        $time -= $hours * 60 * 60;
        $minutes = (int) floor($time / 60);
        $time -= $minutes * 60;
        $seconds = (int) round($time);
        try {
            $duration = 'PT' . $hours . 'H' . $minutes . 'M' . $seconds . 'S';
            $date->add(new DateInterval($duration));
        } catch (Exception) {
            throw new Exception('Invalid duration "' . $duration . '"');
        }

        return $date;
    }
}
