<?php

declare(strict_types=1);

namespace Struct\DataType;

use RuntimeException;
use Struct\Contracts\DataTypeInterface;

readonly abstract class AbstractDataType implements DataTypeInterface
{
    public function __construct(string $serializedData)
    {
        $this->_deserializeFromString($serializedData);
    }

    protected function _deserializeFromString(string $serializedData): void
    {
        throw new RuntimeException('Must be implemented', 1696233161);
    }

    protected function _serializeToString(): string
    {
        throw new RuntimeException('Must be implemented', 1696233161);
    }

    public function serializeToString(): string
    {
        return $this->_serializeToString();
    }

    public function __toString(): string
    {
        return $this->serializeToString();
    }
}
