<?php

declare(strict_types=1);

namespace Struct\DataType;

use Struct\Contracts\Operator\SumInterface;
use Struct\Exception\Operator\DataTypeException;

abstract readonly class AbstractDataTypeSum extends AbstractDataTypeInteger implements SumInterface
{
    public static function sum(array $summandList): static
    {
        $sum = 0;
        foreach ($summandList as $summand) {
            if ($summand instanceof static === false) {
                throw new DataTypeException('All summand must be of type: ' . static::class, 1737810893);
            }
            $sum += $summand->serializeToInt();
        }
        $class = static::class;
        $sumObject = new $class($sum);
        return $sumObject;
    }
}
