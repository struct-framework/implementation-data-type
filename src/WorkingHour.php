<?php

declare(strict_types=1);

namespace Struct\DataType;

use Struct\Contracts\Operator\SignChangeInterface;
use Struct\Contracts\Operator\SumInterface;
use Struct\Contracts\SerializableToInt;
use Struct\Exception\Operator\DataTypeException;

final class WorkingHour extends AbstractDataType implements SerializableToInt, SumInterface, SignChangeInterface
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
        $number = (float) $serializedData;
        $this->minutes = (int) ($number * 60);
        if ($isNegative === true) {
            $this->minutes *= -1;
        }
    }

    protected function _serializeToString(): string
    {
        $minutes = $this->minutes;
        $output =  '';
        if ($minutes < 0) {
            $minutes *= -1;
            $output =  '- ';
        }

        $hours = (string) (int) ($minutes / 60 * 100);
        $length = strlen($hours);

        if (strlen($hours) < 3) {
            $output .= '0.';
            if (strlen($hours) < 2) {
                $output .= '0';
            }
            $output .= $hours;
            return $output;
        }
        $output .= substr($hours, 0, - 2);
        $output .= '.';
        $output .= substr($hours, $length - 2);
        return $output;
    }
}
