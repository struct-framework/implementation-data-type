<?php

declare(strict_types=1);

namespace Struct\DataType;

use RuntimeException;
use Struct\Contracts\DataType\SerializableToInt;
use Struct\Contracts\DataType\SortableInterface;
use Struct\Contracts\Operator\ComparableInterface;
use Struct\Contracts\Operator\IncrementableInterface;
use Struct\Enum\Operator\Comparison;
use Struct\Exception\Operator\CompareException;


readonly abstract class AbstractDataTypeInteger extends AbstractDataType implements SerializableToInt, ComparableInterface, SortableInterface, IncrementableInterface
{
    public function __construct(string|int $serializedData)
    {
        if(is_int($serializedData) === true){
            $this->_deserializeFromInt($serializedData);
            return;
        }
        parent::__construct($serializedData);
    }

    protected function _serializeToInt(): int
    {
        throw new RuntimeException('Must be implemented', 1696233161);
    }


    protected function _deserializeFromInt(int $serializedData): void
    {
        throw new RuntimeException('Must be implemented', 1737446244);
    }

    public function serializeToInt(): int
    {
        return $this->_serializeToInt();
    }

    public function compare(ComparableInterface $compareWith): Comparison
    {
        $selfClassName = get_class($this);
        if ($compareWith::class !== $selfClassName) {
            throw new CompareException('You can only compare same DataTypes try to compare <' .$selfClassName .'> with <'.$compareWith::class.'>', 1737446643);
        }
        $left = $this->serializeToInt();
        $right = $compareWith->serializeToInt();
        if ($left < $right) {
            return Comparison::lessThan;
        }
        if ($left > $right) {
            return Comparison::greaterThan;
        }
        return Comparison::equal;
    }


    public function getSortValue(): int|false
    {
        return $this->serializeToInt();
    }

    public function increment(): self
    {
        $asInt = $this->serializeToInt();
        $asInt++;
        $value = new $this($asInt);
        return $value;
    }

    public function decrement(): self
    {
        $asInt = $this->serializeToInt();
        $asInt--;
        $value = new $this($asInt);
        return $value;
    }
}
