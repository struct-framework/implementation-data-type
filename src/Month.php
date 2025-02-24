<?php

declare(strict_types=1);

namespace Struct\DataType;

use function count;
use function explode;
use InvalidArgumentException;
use function strlen;
use Struct\Exception\DeserializeException;

final readonly class Month extends AbstractDataTypeInteger
{
    public int $year;

    public int $month;

    public function __construct(string|int $serializedDataOrYear, ?int $month = null)
    {
        $year = $serializedDataOrYear;
        if (
            is_int($year) === false || is_int($month) === false
        ) {
            $result = $this->_deserialize($serializedDataOrYear);
            $year = $result[0];
            $month = $result[1];
        }
        $this->year = $year;
        $this->month = $month;
    }

    public function withMonth(int $month): self
    {
        if ($month < 1 || $month > 12) {
            throw new DeserializeException(1740344686, 'The month must be between 1 and 12');
        }
        return self::createByYearMonth($this->year, $month);
    }

    public function withYear(int $year): self
    {
        if ($year < 1000 || $year > 9999) {
            throw new DeserializeException(1740344693, 'The year must be between 1000 and 9999');
        }
        return self::createByYearMonth($year, $this->month);
    }

    public static function createByYearMonth(int $year, int $month): self
    {
        $yearString = (string) $year;
        $monthString = (string) $month;
        if (strlen($monthString) === 1) {
            $monthString = '0' . $monthString;
        }
        $date = new self($yearString . '-' . $monthString);
        return $date;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function firstDayOfMonth(): Date
    {
        $date = new Date($this->year, $this->month, 1);
        return $date;
    }

    public function lastDayOfMonth(): Date
    {
        $firstDayOfMonth = $this->firstDayOfMonth();
        $date = $firstDayOfMonth->lastDayOfMonth();
        return $date;
    }

    protected function _serializeToString(): string
    {
        $monthString = (string) $this->month;
        if (strlen($monthString) === 1) {
            $monthString = '0' . $monthString;
        }
        $serializedData = $this->year . '-' . $monthString;
        return $serializedData;
    }

    /**
     * @return array{0:int, 1:int}
     */
    protected function _deserialize(string|int $serializedData): array
    {
        if (is_int($serializedData) === true) {
            return $this->_deserializeFromInt($serializedData);
        }
        if (strlen($serializedData) !== 7) {
            throw new DeserializeException(1696227826, 'The value serialized data string must have 7 characters');
        }
        $parts = explode('-', $serializedData);
        if (count($parts) !== 2) {
            throw new DeserializeException(1696227896, 'The value serialized data must year und month to parts separate by -');
        }
        $year = (int) $parts[0];
        $month = (int) $parts[1];

        return [$year, $month];
    }

    protected function _serializeToInt(): int
    {
        $monthAsInt = $this->year * 12;
        $monthAsInt += $this->month - 1;
        return $monthAsInt;
    }

    /**
     * @return array{0:int, 1:int}
     */
    protected function _deserializeFromInt(int $serializedData): array
    {
        $year = (int) ($serializedData / 12);
        $month = ($serializedData % 12) + 1;
        return [$year, $month];
    }
}
