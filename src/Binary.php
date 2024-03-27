<?php

declare(strict_types=1);

namespace Struct\DataType;

use function bin2hex;
use function hex2bin;

final class Binary extends AbstractDataType
{
    protected string $binaryString = '';

    public function __construct(?string $serializedData = null, bool $isBinaryString = false)
    {
        parent::__construct();
        if ($serializedData === null) {
            return;
        }
        if ($isBinaryString === false) {
            $this->_deserializeFromString($serializedData);
            return;
        }
        $this->binaryString = $serializedData;
    }

    protected function _deserializeFromString(string $serializedData): void
    {
        $deserializedData = hex2bin($serializedData);
        if ($deserializedData === false) {
            throw new \InvalidArgumentException('The $serializedData must be an valid hex string', 1711527719);
        }
        $this->binaryString = $deserializedData;
    }

    protected function _serializeToString(): string
    {
        $serializedData = bin2hex($this->binaryString);
        return $serializedData;
    }

    public function getBinaryString(): string
    {
        return $this->binaryString;
    }

    public function setBinaryString(string $binaryString): void
    {
        $this->binaryString = $binaryString;
    }
}
