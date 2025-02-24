<?php

declare(strict_types=1);

namespace Struct\DataType\Internal\Helper;

use function count;
use function explode;
use function str_starts_with;
use function strlen;
use Struct\Exception\DeserializeException;

final class NumberStringToNumberInt
{
    /**
     * @param string $number
     * @return array<int>
     */
    public static function numberStringToNumberInt(string $number): array
    {
        $numberParts = explode('.', $number);
        if (count($numberParts) > 2) {
            throw new DeserializeException(1696315411, 'The amount must not have more than one decimal: ' . $number);
        }
        $numberFull = $numberParts[0];
        $numberFraction = '';
        if (count($numberParts) === 2) {
            $numberFraction = $numberParts[1];
        }

        $decimals = strlen($numberFraction);
        $numberString = $numberFull . $numberFraction;
        $numberInt = (int) $numberString;

        while (str_starts_with($numberString, '0')) {
            $numberString = substr($numberString, 1);
        }

        if ($numberString === '') {
            $numberString = '0';
        }

        if ((string) $numberInt !== $numberString) {
            throw new DeserializeException(1696315612, 'Invalid character in amount: ' . $numberString);
        }

        return [
            $numberInt,
            $decimals,
        ];
    }
}
