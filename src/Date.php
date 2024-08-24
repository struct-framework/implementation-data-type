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
use Struct\Contracts\Operator\IncrementableInterface;
use Struct\Contracts\SerializableToInt;
use Struct\Contracts\SortableInterface;
use Struct\DataType\Enum\Weekday;
use Struct\Enum\Operator\Comparison;
use Struct\Exception\DeserializeException;
use Struct\Exception\Operator\CompareException;
use Throwable;

final class Date extends AbstractDataType implements SerializableToInt, ComparableInterface, IncrementableInterface, SortableInterface
{
    protected int $year;

    protected int $month;

    protected int $day;

    public function __construct(null|string|DateTime $serializedData = null)
    {
        if (is_string($serializedData) === true) {
            parent::__construct($serializedData);
        }
        if ($serializedData instanceof DateTime) {
            $this->deserializeFromDateTime($serializedData);
        }
    }

    public function setYear(int $year): void
    {
        if ($year < 1000 || $year > 9999) {
            throw new InvalidArgumentException('The year must be between 1000 and 9999', 1696052931);
        }
        $this->year = $year;
        $this->checkDay();
    }

    public function setMonth(int $month): void
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('The month must be between 1 and 12', 1696052867);
        }
        $this->month = $month;
        $this->checkDay();
    }

    public function setDay(int $day): void
    {
        if ($day < 1 || $day > 31) {
            throw new InvalidArgumentException('The day must be between 1 and 31', 1696052931);
        }
        $this->day = $day;
        $this->checkDay();
    }

    public function setDate(int $year, int $month, int $day): void
    {
        $this->setYear($year);
        $this->setMonth($month);
        $this->setDay($day);
    }

    protected function checkDay(): void
    {
        if (isset($this->year) === false) {
            return;
        }
        if (isset($this->month) === false) {
            return;
        }
        if (isset($this->day) === false) {
            return;
        }
        $checkDate = new DateTime($this->year . '-' . $this->month . '-01', new DateTimeZone('UTC'));
        $checkDate->setTime(0, 0);
        $numberOfDays = (int) $checkDate->format('t');
        if ($this->day > $numberOfDays) {
            throw new InvalidArgumentException('The month: ' . $this->month . ' in the year: ' . $this->year . ' has only: ' . $numberOfDays . ' days. Given: ' . $this->day, 1696334057);
        }
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function getDay(): int
    {
        return $this->day;
    }

    protected function _serializeToString(): string
    {
        $yearString = (string) $this->year;
        $monthString = (string) $this->month;
        $dayString = (string) $this->day;
        if (strlen($monthString) === 1) {
            $monthString = '0' . $monthString;
        }
        if (strlen($dayString) === 1) {
            $dayString = '0' . $dayString;
        }
        $serializedData = $yearString . '-' . $monthString . '-' . $dayString;
        return $serializedData;
    }

    public function reset(): void
    {
        $this->year = 1000;
        $this->month = 1;
        $this->day = 1;
    }

    protected function _deserializeFromString(string $serializedData): void
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

        $this->reset();

        try {
            $this->setYear($year);
        } catch (InvalidArgumentException $exception) {
            throw new DeserializeException('Invalid year: ' . $exception->getMessage(), 1696334757, $exception);
        }

        try {
            $this->setMonth($month);
        } catch (InvalidArgumentException $exception) {
            throw new DeserializeException('Invalid month: ' . $exception->getMessage(), 1696334760, $exception);
        }

        try {
            $this->setDay($day);
        } catch (InvalidArgumentException $exception) {
            throw new DeserializeException('Invalid day: ' . $exception->getMessage(), 1696334763, $exception);
        }
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

    /**
     * @var array<int>
     */
    protected static array $daysPerMonth = [31, 28, 31 , 30, 31, 30, 31, 31, 30, 31, 30, 31];

    public function serializeToInt(): int
    {
        $isLeapYear = self::isLeapYear($this->year);
        $month = $this->month - 1;
        $day = $this->day - 1;

        $days = self::calculateDaysByYear($this->year);

        for ($index = 0; $index < $month; $index++) {
            $days += self::$daysPerMonth[$index];
            if ($index === 1 && $isLeapYear === true) {
                $days++;
            }
        }
        $days += $day;
        return $days;
    }

    public function deserializeFromInt(int $serializedData): void
    {
        if ($serializedData < 0 || $serializedData > 3287181) {
            throw new DeserializeException('The value of serialized data string must be between 0 and 3287181', 1700914014);
        }
        $days = $serializedData;
        $remainingDays = 0;
        $this->year = self::calculateYearByDays($days, $remainingDays);
        $isLeapYear = self::isLeapYear($this->year);
        $moth = 0;
        foreach (self::$daysPerMonth as $daysPerMonth) {
            if ($moth === 1 && $isLeapYear === true) {
                $daysPerMonth++;
            }
            if ($daysPerMonth > $remainingDays) {
                break;
            }
            $moth++;
            $remainingDays -= $daysPerMonth;
        }
        $this->month = $moth + 1;
        $this->day = $remainingDays + 1;
    }

    public function getSortValue(): int|false
    {
        return $this->serializeToInt();
    }

    protected static int $dayShift = 364877;

    /**
     * @var array<int, int>
     */
    protected static array $daysForYearSpan = [
        400 => 146097,  // [100] * 4   + 1;
        100 => 36524,   // [4]   * 25  - 1;
        4   => 1461,    // [1]   * 4   + 1;
        1    => 365,
    ];

    public static function calculateDaysByYear(int $year): int
    {
        $year--;
        $days = 0;
        foreach (self::$daysForYearSpan as $left => $right) {
            $fraction = (int) floor($year / $left);
            $year -= $fraction * $left;
            $days += $fraction * $right;
        }
        $days -= self::$dayShift;
        return $days;
    }

    public static function calculateYearByDays(int $days, int &$remainingDays = 0): int
    {
        $year = 0;
        $days += self::$dayShift;
        foreach (self::$daysForYearSpan as $left => $right) {
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
        $daysInMonth = self::$daysPerMonth[$month];
        if ($month === 1 && self::isLeapYear($year)) {
            $daysInMonth++;
        }
        return $daysInMonth;
    }

    public function compare(ComparableInterface $compareWith): Comparison
    {
        if ($compareWith instanceof self === false) {
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

    public function increment(): void
    {
        $days = $this->serializeToInt();
        $this->deserializeFromInt(++$days);
    }

    public function decrement(): void
    {
        $days = $this->serializeToInt();
        $this->deserializeFromInt(--$days);
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

    public function deserializeFromDateTime(DateTime $dateTime): void
    {
        $this->_deserializeFromString($dateTime->format('Y-m-d'));
    }

    public function firstDayOfTheYear(): self
    {
        $firstDayOfTheYear = new self();
        $firstDayOfTheYear->day = 1;
        $firstDayOfTheYear->month = 1;
        $firstDayOfTheYear->year = $this->year;
        return $firstDayOfTheYear;
    }

    public function isFirstDayOfTheYear(): bool
    {
        if($this->month === 1 && $this->day === 1) {
            return true;
        }
        return false;
    }

    public function lastDayOfTheYear(): self
    {
        $lastDayOfTheYear = new self();
        $lastDayOfTheYear->day = 31;
        $lastDayOfTheYear->month = 12;
        $lastDayOfTheYear->year = $this->year;
        return $lastDayOfTheYear;
    }

    public function isLastDayOfTheYear(): bool
    {
        if($this->month === 12 && $this->day === 31) {
            return true;
        }
        return false;
    }

    public function lastDayInPreviousYear(): self
    {
        $lastDayInPreviousYear = $this->lastDayOfTheYear();
        $lastDayInPreviousYear->year--;
        return $lastDayInPreviousYear;
    }

    public function firstDayOfMonth(): self
    {
        $firstDayOfMonth = new self();
        $firstDayOfMonth->setYear($this->year);
        $firstDayOfMonth->setMonth($this->month);
        $firstDayOfMonth->setDay(1);
        return $firstDayOfMonth;
    }

    public function isFirstDayOfMonth(): bool
    {
        if($this->day === 1) {
            return true;
        }
        return false;
    }

    public function lastDayOfMonth(): self
    {
        $lastDayOfMonth = new self();
        $lastDayOfMonth->setYear($this->year);
        $lastDayOfMonth->setMonth($this->month);
        $lastDayOfMonth->setDay(self::daysInMonth($this->year, $this->month));
        return $lastDayOfMonth;
    }

    public function isLastDayOfMonth(): bool
    {
        if($this->day === self::daysInMonth($this->year, $this->month)) {
            return true;
        }
        return false;
    }

    public function toMonth(): Month
    {
        $month = new Month();
        $month->setYear($this->year);
        $month->setMonth($this->month);
        return $month;
    }

    public function toYear(): Year
    {
        $year = new Year();
        $year->setYear($this->year);
        return $year;
    }
}
