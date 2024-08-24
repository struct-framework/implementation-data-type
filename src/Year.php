<?php

declare(strict_types=1);

namespace Struct\DataType;

use InvalidArgumentException;
use function strlen;
use Struct\Contracts\Operator\IncrementableInterface;
use Struct\Contracts\SerializableToInt;
use Struct\Contracts\SortableInterface;
use Struct\Exception\DeserializeException;

final class Year extends AbstractDataType implements SerializableToInt, IncrementableInterface, SortableInterface
{
    protected int $year;

    public function setYear(int $year): void
    {
        if ($year < 1000 || $year > 9999) {
            throw new InvalidArgumentException('The year must be between 1000 and 9999', 1724310985);
        }
        $this->year = $year;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    protected function _serializeToString(): string
    {
        $serializedData = (string) $this->year;
        return $serializedData;
    }

    protected function _deserializeFromString(string $serializedData): void
    {
        if (strlen($serializedData) !== 4) {
            throw new DeserializeException('The value serialized data string must have 4 characters', 1724309918);
        }
        $year = (int) $serializedData;

        try {
            $this->setYear($year);
        } catch (InvalidArgumentException $exception) {
            throw new DeserializeException('Invalid year: ' . $exception->getMessage(), 1724309940, $exception);
        }
    }

    public function serializeToInt(): int
    {
        return $this->year;
    }

    public function deserializeFromInt(int $serializedData): void
    {
        $this->setYear($serializedData);
    }

    public function getSortValue(): int|false
    {
        return $this->serializeToInt();
    }

    public function increment(): void
    {
        $this->year++;
    }

    public function decrement(): void
    {
        $this->year--;
    }


    public function firstDayOfTheYear(): Date
    {
        $date = new Date();
        $date->setYear($this->year);
        $date->setMonth(1);
        $date->setDay(1);
        return $date;
    }

    public function lastDayOfYear(): Date
    {
        $date = new Date();
        $date->setYear($this->year);
        $date->setMonth(12);
        $date->setDay(31);
        return $date;
    }
}
