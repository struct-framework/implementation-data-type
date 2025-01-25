<?php

declare(strict_types=1);

namespace Struct\DataType;

use function count;
use DateTime;
use DateTimeZone;
use Exception\Unexpected\UnexpectedException;
use function explode;
use InvalidArgumentException;
use function strlen;
use Struct\Contracts\Operator\ComparableInterface;
use Struct\DataType\Enum\Weekday;
use Struct\Enum\Operator\Comparison;
use Struct\Exception\DeserializeException;
use Struct\Exception\Operator\CompareException;
use Throwable;

final readonly class Date extends AbstractDataTypeInteger
{
    /**
     * @var array<int>
     */
    protected const array DAYS_PER_MONTH  = [31, 28, 31 , 30, 31, 30, 31, 31, 30, 31, 30, 31];
    protected const int DAY_SHIFT = 364877;
    /**
     * @var array<int, int>
     */
    protected const array DAYS_FOR_YEAR_SPAN = [
        400 => 146097,  // [100] * 4   + 1;
        100 => 36524,   // [4]   * 25  - 1;
        4   => 1461,    // [1]   * 4   + 1;
        1    => 365,
    ];

    public int $year;
    public int $month;
    public int $day;

    public function __construct(string|int|DateTime $serializedData)
    {
        $result = $this->_deserialize($serializedData);
        $this->year = $result[0];
        $this->month = $result[1];
        $this->day = $result[2];
        $this->_checkDay($this->year, $this->month, $this->day);
    }

    /**
     * @return array{0:int, 1:int, 2:int}
     */
    protected function _deserialize(string|int|DateTime $serializedData): array
    {
        if (is_string($serializedData) === true) {
            return $this->_deserializeFromString($serializedData);
        }
        if (is_int($serializedData) === true) {
            return $this->_deserializeFromInt($serializedData);
        }
        return $this->_deserializeFromDateTime($serializedData);
    }

    /**
     * @return array{0:int, 1:int, 2:int}
     */
    protected function _deserializeFromString(string $serializedData): array
    {
        if (strlen($serializedData) !== 10) {
            throw new DeserializeException('The value serialized data string must have 10 characters', 1696334669);
        }
        $parts = explode('-', $serializedData);
        if (count($parts) !== 3) {
            throw new DeserializeException('The value serialized data must have year, month and day separate by -', 1696334753);
        }
        $year = (int) $parts[0];
        $month = (int) $parts[1];
        $day = (int) $parts[2];
        return [$year, $month, $day];
    }

    /**
     * @return array{0:int, 1:int, 2:int}
     */
    protected function _deserializeFromInt(int $serializedData): array
    {
        if ($serializedData < 0 || $serializedData > 3287181) {
            throw new DeserializeException('The value of serialized data string must be between 0 and 3287181', 1700914014);
        }
        $days = $serializedData;
        $remainingDays = 0;
        $year = self::calculateYearByDays($days, $remainingDays);
        $isLeapYear = self::isLeapYear($year);
        $month = 0;
        foreach (self::DAYS_PER_MONTH as $daysPerMonth) {
            if ($month === 1 && $isLeapYear === true) {
                $daysPerMonth++;
            }
            if ($daysPerMonth > $remainingDays) {
                break;
            }
            $month++;
            $remainingDays -= $daysPerMonth;
        }
        $month++;
        $day = $remainingDays + 1;

        return [
            $year,
            $month,
            $day,
        ];
    }

    /**
     * @return array{0:int, 1:int, 2:int}
     */
    protected function _deserializeFromDateTime(DateTime $dateTime): array
    {
        return $this->_deserializeFromString($dateTime->format('Y-m-d'));
    }

    protected function _serializeToString(): string
    {
        $serializedData = self::_yearMonthDayToString($this->year, $this->month, $this->day);
        return $serializedData;
    }

    public function toDateTime(): DateTime
    {
        try {
            $dateTime = new DateTime($this->serializeToString() . ' 00:00:00', new DateTimeZone('UTC'));
        } catch (Throwable $exception) {
            throw new UnexpectedException(1700915819, $exception);
        }
        return $dateTime;
    }

    protected function _serializeToInt(): int
    {
        $isLeapYear = self::isLeapYear($this->year);
        $month = $this->month - 1;
        $day = $this->day - 1;

        $days = self::calculateDaysByYear($this->year);

        for ($index = 0; $index < $month; $index++) {
            $days += self::DAYS_PER_MONTH[$index];
            if ($index === 1 && $isLeapYear === true) {
                $days++;
            }
        }
        $days += $day;
        return $days;
    }

    public static function calculateDaysByYear(int $year): int
    {
        $year--;
        $days = 0;
        foreach (self::DAYS_FOR_YEAR_SPAN as $left => $right) {
            $fraction = (int) floor($year / $left);
            $year -= $fraction * $left;
            $days += $fraction * $right;
        }
        $days -= self::DAY_SHIFT;
        return $days;
    }

    public static function calculateYearByDays(int $days, int &$remainingDays = 0): int
    {
        $year = 0;
        $days += self::DAY_SHIFT;
        foreach (self::DAYS_FOR_YEAR_SPAN as $left => $right) {
            if ($days === 0) {
                break;
            }
            if ($days === 146096) {
                $year += 399;
                $days = 365;
                break;
            }
            if ($days === 1460) {
                $year += 3;
                $days = 365;
                break;
            }
            $fraction = (int) floor($days / $right);
            $days -= $fraction * $right;
            $year += $fraction * $left;
        }
        $year++;
        $remainingDays = $days;
        return $year;
    }

    public static function isLeapYear(int $year): bool
    {
        if ($year < 1000 || $year > 9999) {
            throw new InvalidArgumentException('The year must be between 1000 and 9999', 1706731139);
        }
        if ($year % 400 === 0) {
            return true;
        }
        if ($year % 100 === 0) {
            return false;
        }
        if ($year % 4 === 0) {
            return true;
        }
        return false;
    }

    public static function daysInMonth(int $year, int $month): int
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('The month must be between 1 and 12', 1706731136);
        }
        $month--;
        $daysInMonth = self::DAYS_PER_MONTH[$month];
        if ($month === 1 && self::isLeapYear($year)) {
            $daysInMonth++;
        }
        return $daysInMonth;
    }

    public function compare(ComparableInterface $compareWith): Comparison
    {
        if ($compareWith::class !== self::class) {
            throw new CompareException('Date can only compare with date', 1700916002);
        }
        if ($this->year < $compareWith->year) {
            return Comparison::lessThan;
        }
        if ($this->year > $compareWith->year) {
            return Comparison::greaterThan;
        }
        if ($this->month < $compareWith->month) {
            return Comparison::lessThan;
        }
        if ($this->month > $compareWith->month) {
            return Comparison::greaterThan;
        }
        if ($this->day < $compareWith->day) {
            return Comparison::lessThan;
        }
        if ($this->day > $compareWith->day) {
            return Comparison::greaterThan;
        }
        return Comparison::equal;
    }

    public function weekday(): Weekday
    {
        $weekdayNumber = $this->weekdayNumber();
        $weekday =  Weekday::from($weekdayNumber);
        return $weekday;
    }

    public function weekdayNumber(): int
    {
        $days = $this->serializeToInt();
        $days += 2;
        $weekdayNumber = $days % 7;
        return $weekdayNumber;
    }

    public function calendarWeek(): int
    {
        $firstDayOfTheYear = $this->firstDayOfTheYear();
        $numberOfDayInYear = $this->serializeToInt() - $firstDayOfTheYear->serializeToInt() + $firstDayOfTheYear->weekdayNumber();
        $calendarWeek = (int) ($numberOfDayInYear / 7);
        if ($firstDayOfTheYear->weekdayNumber() < 4) {
            $calendarWeek++;
        }
        if ($calendarWeek === 0) {
            $lastDayInPreviousYear = $this->lastDayInPreviousYear();
            return $lastDayInPreviousYear->calendarWeek();
        }

        if ($calendarWeek === 53) {
            $lastDayOfTheYear = $this->lastDayOfTheYear();
            if ($lastDayOfTheYear->weekdayNumber() < 3) {
                $calendarWeek = 1;
            }
        }

        return $calendarWeek;
    }

    public function firstDayOfTheYear(): self
    {
        $date = self::createByYearMonthDay($this->year, 1, 1);
        return $date;
    }

    public function isFirstDayOfTheYear(): bool
    {
        if ($this->month === 1 && $this->day === 1) {
            return true;
        }
        return false;
    }

    public function lastDayOfTheYear(): self
    {
        $date = self::createByYearMonthDay($this->year, 12, 31);
        return $date;
    }

    public function isLastDayOfTheYear(): bool
    {
        if ($this->month === 12 && $this->day === 31) {
            return true;
        }
        return false;
    }

    public function lastDayInPreviousYear(): self
    {
        $date = self::createByYearMonthDay($this->year - 1, 12, 31);
        return $date;
    }

    public function firstDayOfMonth(): self
    {
        $date = self::createByYearMonthDay($this->year, $this->month, 1);
        return $date;
    }

    public function isFirstDayOfMonth(): bool
    {
        if ($this->day === 1) {
            return true;
        }
        return false;
    }

    public function lastDayOfMonth(): self
    {
        $day = self::daysInMonth($this->year, $this->month);
        $date = self::createByYearMonthDay($this->year, $this->month, $day);
        return $date;
    }

    public function isLastDayOfMonth(): bool
    {
        if ($this->day === self::daysInMonth($this->year, $this->month)) {
            return true;
        }
        return false;
    }

    public function toMonth(): Month
    {
        $month = new Month($this->year . '-' . $this->month);
        return $month;
    }

    public function toYear(): Year
    {
        $year = new Year($this->year);
        return $year;
    }

    public function withYear(int $year): self
    {
        $date =self::createByYearMonthDay($year, $this->month, $this->day);
        return $date;
    }

    public function withMonth(int $month): self
    {
        $date = self::createByYearMonthDay($this->year, $month, $this->day);
        return $date;
    }

    public function withDay(int $day): self
    {
        $date = self::createByYearMonthDay($this->year, $this->month, $day);
        return $date;
    }

    public static function createByYearMonthDay(int $year, int $month, int $day): self
    {
        $serializedData = self::_yearMonthDayToString($year, $month, $day);
        $date = new self($serializedData);
        return $date;
    }

    protected static function _yearMonthDayToString(int $year, int $month, int $day): string
    {
        $yearString = (string) $year;
        $monthString = (string) $month;
        $dayString = (string) $day;
        if (strlen($monthString) === 1) {
            $monthString = '0' . $monthString;
        }
        if (strlen($dayString) === 1) {
            $dayString = '0' . $dayString;
        }
        $serializedData = $yearString . '-' . $monthString . '-' . $dayString;
        return $serializedData;
    }

    protected function _checkDay(int $year, int $month, int $day): void
    {
        if ($year < 1000 || $year > 9999) {
            throw new InvalidArgumentException('The year must be between 1000 and 9999', 1737815639);
        }
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('The month must be between 1 and 12', 1737815636);
        }
        $days = self::daysInMonth($year, $month);
        if ($day < 1 || $day > $days) {
            throw new InvalidArgumentException('The day must be between 1 and ' . $days, 1737815642);
        }
    }
}
