<?php

declare(strict_types=1);

namespace Struct\DataType;


use Struct\Contracts\Operator\SignChangeInterface;
use Struct\Exception\InvalidFormatException;
use Struct\Exception\Operator\DataTypeException;

final readonly class WorkingTime extends AbstractDataTypeSum implements SignChangeInterface
{
    /**
     * @var array<string, int>
     */
    protected const array STEPS = [
        'mo' => 48000,
        'w' => 2400,
        'd' => 480,
        'h' => 60,
        'm' => 1
    ];

    public int $minutes;

    public function __construct(string|int $serializedData)
    {
        $minutes  = $this->_deserialize($serializedData);
        $this->minutes = $minutes;
    }


    protected function _serializeToInt(): int
    {
        return $this->minutes;
    }

    protected function _deserialize(int|string $serializedData): int
    {
        if(is_int($serializedData) === true) {
            return $serializedData;
        }
        if ($serializedData === '') {
            return 0;
        }

        $isNegative = false;
        if (str_starts_with($serializedData, '- ') === true) {
            $isNegative = true;
            $serializedData = substr($serializedData, 2);
        }

        $minutes = 0;
        $parts = explode(' ', $serializedData);

        foreach ($parts as $part) {
            foreach (self::STEPS as $key => $value) {
                if (str_ends_with($part, $key) === false) {
                    continue;
                }
                $numberString = substr($part, 0, strlen($key) * -1);
                $numberInt = (int) $numberString;

                if ($numberInt < 0) {
                    throw new InvalidFormatException($serializedData, '1mo 1w 2d 5h 9m', 1707057960);
                }
                if ($numberString !== (string) $numberInt) {
                    throw new InvalidFormatException($serializedData, '1mo 1w 2d 5h 9m', 1707057655);
                }
                $minutes += $numberInt * $value;
                continue 2;
            }
            throw new InvalidFormatException($serializedData, '1mo 1w 2d 5h 9m', 1707057655);
        }

        if ($isNegative === true) {
            $minutes *= -1;
        }
        return $minutes;
    }

    protected function _serializeToString(): string
    {
        $minutes = $this->minutes;
        $isNegative = false;
        if ($minutes < 0) {
            $minutes *= -1;
            $isNegative = true;
        }
        $parts = [];
        foreach (self::STEPS as $key => $step) {
            $part = (int) ($minutes / $step);
            $minutes -= $part * $step;

            if ($part > 0) {
                $parts[] = $part . $key;
            }
        }
        $output = implode(' ', $parts);
        if ($isNegative === true) {
            $output = '- ' . $output;
        }
        return $output;
    }

    public static function signChange(SignChangeInterface $left): self
    {
        if ($left instanceof static === false) {
            throw new DataTypeException('The value must be of DataType: ' . static::class, 1737818254);
        }
        return new static($left->serializeToInt() * -1);
    }
}
