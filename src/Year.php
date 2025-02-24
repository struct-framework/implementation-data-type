<?php

declare(strict_types=1);

namespace Struct\DataType;

use Struct\Exception\DeserializeException;

final readonly class Year extends AbstractDataTypeInteger
{
    public int $year;

    public function __construct(string|int $serializedData)
    {
        $year = (int) $serializedData;
        if ($year < 1000 || $year > 9999) {
            throw new DeserializeException(1737809871, 'The value serialized data $year string must be between 1000 and 9999');
        }
        $this->year = $year;
    }

    protected function _serializeToString(): string
    {
        $serializedData = (string) $this->year;
        return $serializedData;
    }

    protected function _serializeToInt(): int
    {
        return $this->year;
    }

    public function firstDayOfTheYear(): Date
    {
        $date = new Date($this->year, 1, 1);
        return $date;
    }

    public function lastDayOfYear(): Date
    {
        $data = new Date($this->year, 12, 31);
        return $data;
    }

    public static function createByYear(int $year): self
    {
        $yearString = (string) $year;
        $data = new self($yearString);
        return $data;
    }
}
