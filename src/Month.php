<?php

declare(strict_types=1);

namespace Struct\DataType;

use InvalidArgumentException;
use Struct\Contracts\Operator\IncrementableInterface;
use Struct\Contracts\SerializableToInt;
use Struct\Exception\DeserializeException;

final class Month extends AbstractDataType implements SerializableToInt, IncrementableInterface
{
    protected int $year;

    protected int $month;

    public function setMonth(int $month): void
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('The month must be between 1 and 12', 1696052867);
        }
        $this->month = $month;
    }

    public function setYear(int $year): void
    {
        if ($year < 1000 || $year > 9999) {
            throw new InvalidArgumentException('The year must be between 1000 and 9999', 1696052931);
        }
        $this->year = $year;
    }

    public function setYearAndMonth(int $year, int $month): void
    {
        $this->setYear($year);
        $this->setMonth($month);
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
        $date = new Date();
        $date->setYear($this->year);
        $date->setMonth($this->month);
        $date->setDay(1);
        return $date;
    }

    public function lastDayOfMonth(): Date
    {
        $firstDayOfMonth = $this->firstDayOfMonth();
        return $firstDayOfMonth->lastDayOfMonth();
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

    protected function _deserializeFromString(string $serializedData): void
    {
        if (\strlen($serializedData) !== 7) {
            throw new DeserializeException('The value serialized data string must have 7 characters', 1696227826);
        }
        $parts = \explode('-', $serializedData);
        if (\count($parts) !== 2) {
            throw new DeserializeException('The value serialized data must year und month to parts separate by -', 1696227896);
        }
        $year = (int) $parts[0];
        $month = (int) $parts[1];

        try {
            $this->setYear($year);
        } catch (InvalidArgumentException $exception) {
            throw new DeserializeException('Invalid year: ' . $exception->getMessage(), 1696228152, $exception);
        }

        try {
            $this->setMonth($month);
        } catch (InvalidArgumentException $exception) {
            throw new DeserializeException('Invalid month: ' . $exception->getMessage(), 1696228168, $exception);
        }
    }

    public function serializeToInt(): int
    {
        $monthAsInt = $this->year * 12;
        $monthAsInt += $this->month - 1;
        return $monthAsInt;
    }

    public function deserializeFromInt(int $serializedData): void
    {
        $year = (int) ($serializedData / 12);
        $month = ($serializedData % 12) + 1;
        $this->setYearAndMonth($year, $month);
    }

    public function increment(): void
    {
        $this->month++;
        if ($this->month > 12) {
            $this->month = 1;
            $this->year++;
        }
    }

    public function decrement(): void
    {
        $this->month--;
        if ($this->month < 1) {
            $this->month = 12;
            $this->year--;
        }
    }
}
