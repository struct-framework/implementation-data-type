<?php

declare(strict_types=1);

namespace Struct\DataType;

use Struct\Exception\DeserializeException;
use function strlen;

final readonly class Year extends AbstractDataTypeInteger
{
    protected int $year;

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
        $this->year = (int) $serializedData;
    }

    protected function _serializeToInt(): int
    {
        return $this->year;
    }


    protected function _deserializeFromInt(int $serializedData): void
    {
        $this->year = $serializedData;
    }


    public function firstDayOfTheYear(): Date
    {
        $date = Date::createByYearMonthDay($this->year, 1, 1);
        return $date;
    }

    public function lastDayOfYear(): Date
    {
        $data = Date::createByYearMonthDay($this->year, 12, 31);
        return $data;
    }

    public static function createByYear(int $year): self
    {
        $yearString = (string) $year;
        $data = new self($yearString);
        return $data;
    }
}
