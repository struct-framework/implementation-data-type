<?php

declare(strict_types=1);

namespace Struct\DataType;

use Struct\Exception\Operator\DataTypeException;

final readonly class WorkingHour extends AbstractDataTypeInteger
{
    public int $minutes;

    public function __construct(string|int $serializedData)
    {
        parent::__construct($serializedData);
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

    protected function _serializeToInt(): int
    {
        return $this->minutes;
    }


    protected function _deserializeFromInt(int $serializedData): void
    {
        $this->minutes = $serializedData;
    }


    protected function _deserializeFromString(string $serializedData): void
    {
        $this->minutes = self::intFromString($serializedData);
    }

    protected static function intFromString(string $serializedData): int
    {
        if ($serializedData === '') {
            return 0;
        }
        $isNegative = false;
        if (str_starts_with($serializedData, '- ') === true) {
            $isNegative = true;
            $serializedData = substr($serializedData, 2);
        }
        $number = (float) $serializedData;
        $minutes = (int) ($number * 60);
        if ($isNegative === true) {
            $minutes *= -1;
        }
        return $minutes;
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
