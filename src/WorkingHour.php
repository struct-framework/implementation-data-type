<?php

declare(strict_types=1);

namespace Struct\DataType;

use Struct\Contracts\Operator\SignChangeInterface;
use Struct\Exception\Operator\DataTypeException;

final readonly class WorkingHour extends AbstractDataTypeSum implements SignChangeInterface
{
    public int $minutes;

    public function __construct(string|int $serializedData)
    {
        $minutes = $this->_deserialize($serializedData);
        $this->minutes = $minutes;
    }

    protected function _serializeToInt(): int
    {
        return $this->minutes;
    }

    protected function _deserialize(string|int $serializedData): int
    {
        if (is_int($serializedData) === true) {
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

    public static function signChange(SignChangeInterface $left): self
    {
        if ($left instanceof static === false) {
            throw new DataTypeException(1737818254, 'The value must be of DataType: ' . static::class);
        }
        return new static($left->serializeToInt() * -1);
    }
}
