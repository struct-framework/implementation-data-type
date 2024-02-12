<?php

declare(strict_types=1);

namespace Struct\DataType;

use Struct\Contracts\Operator\SignChangeInterface;
use Struct\Contracts\Operator\SumInterface;
use Struct\Contracts\SerializableToInt;
use Struct\Exception\InvalidFormatException;
use Struct\Exception\Operator\DataTypeException;

final class WorkingTime extends AbstractDataType implements SerializableToInt, SumInterface, SignChangeInterface
{
    public int $minutes = 0;

    public function __construct(string|int|null $serializedData = null)
    {
        if (is_int($serializedData) === true) {
            $this->minutes = $serializedData;
            return;
        }
        parent::__construct($serializedData);
    }

    public static function signChange(SignChangeInterface $left): self
    {
        /** @var self $result */
        $result = clone $left;
        $result->minutes *= -1;
        return $result;
    }

    public static function sum(array $summandList): self
    {
        $minutes  = 0;
        foreach ($summandList as $summand) {
            if ($summand instanceof self === false) {
                throw new DataTypeException('All summand must be of type: ' . self::class, 1707058977);
            }
            $minutes += $summand->minutes;
        }
        $workingTime = new self();
        $workingTime->minutes = $minutes;
        return $workingTime;
    }

    public function serializeToInt(): int
    {
        return $this->minutes;
    }

    public function deserializeFromInt(int $serializedData): void
    {
        $this->minutes = $serializedData;
    }

    /**
     * @var array<string, int>
     */
    protected array $steps = [
        'mo' => 48000,
        'w' => 2400,
        'd' => 480,
        'h' => 60,
        'm' => 1
    ];

    protected function _deserializeFromString(string $serializedData): void
    {
        if ($serializedData === '') {
            $this->minutes = 0;
            return;
        }

        $isNegative = false;
        if (str_starts_with($serializedData, '- ') === true) {
            $isNegative = true;
            $serializedData = substr($serializedData, 2);
        }

        $minutes = 0;
        $parts = explode(' ', $serializedData);

        foreach ($parts as $part) {
            foreach ($this->steps as $key => $value) {
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
        $this->minutes = $minutes;
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
        foreach ($this->steps as $key => $step) {
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
}
