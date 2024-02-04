<?php

declare(strict_types=1);

namespace Struct\DataType\Private\Helper;

use Struct\Exception\DeserializeException;

final class NumberStringToNumberInt
{
    /**
     * @param string $number
     * @return array<int>
     */
    public static function numberStringToNumberInt(string $number): array
    {
        $numberParts = \explode('.', $number);
        if (\count($numberParts) > 2) {
            throw new DeserializeException('The amount must not have more than one decimal: ' . $number, 1696315411);
        }
        $numberFull = $numberParts[0];
        $numberFraction = '';
        if (\count($numberParts) === 2) {
            $numberFraction = $numberParts[1];
        }

        $decimals = \strlen($numberFraction);
        $numberString = $numberFull . $numberFraction;
        $numberInt = (int) $numberString;

        while (\str_starts_with($numberString, '0')) {
            $numberString = substr($numberString, 1);
        }

        if ($numberString === '') {
            $numberString = '0';
        }

        if ((string) $numberInt !== $numberString) {
            throw new DeserializeException('Invalid character in amount: ' . $numberString, 1696315612);
        }

        return [
            $numberInt,
            $decimals,
        ];
    }
}
