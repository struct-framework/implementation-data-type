<?php

declare(strict_types=1);

namespace Struct\DataType;

use RuntimeException;
use Struct\Contracts\DataType\SerializableToInt;
use Struct\Contracts\DataType\SortableInterface;
use Struct\Contracts\Operator\ComparableInterface;
use Struct\Contracts\Operator\IncrementableInterface;
use Struct\Contracts\Operator\SumInterface;
use Struct\Enum\Operator\Comparison;
use Struct\Exception\Operator\CompareException;
use Struct\Exception\Operator\DataTypeException;


readonly abstract class AbstractDataTypeSum extends AbstractDataTypeInteger implements SumInterface
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
