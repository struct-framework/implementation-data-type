<?php

declare(strict_types=1);

namespace Struct\DataType;

use function bin2hex;
use function hex2bin;

final readonly class Binary extends AbstractDataType
{
    protected string $binaryString;

    public function __construct(string $serializedData, bool $isBinaryString = false)
    {
        if ($isBinaryString === false) {
            $deserializedData = hex2bin($serializedData);
            if ($deserializedData === false) {
                throw new \InvalidArgumentException('The $serializedData must be an valid hex string', 1737813403);
            }
            $this->binaryString = $deserializedData;
            return;
        }
        $this->binaryString = $serializedData;
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
}
