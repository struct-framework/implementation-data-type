<?php

declare(strict_types=1);

namespace Struct\DataType;

use RuntimeException;
use Struct\Contracts\DataTypeInterface;

abstract readonly class AbstractDataType implements DataTypeInterface
{
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
        return $this->_serializeToString();
    }
}
