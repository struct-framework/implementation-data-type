<?php

declare(strict_types=1);

namespace Struct\DataType;

use function strlen;
use Struct\DataType\Enum\RateType;
use Struct\DataType\Private\Helper\NumberStringToNumberInt;
use Struct\Exception\DeserializeException;
use function substr;

final readonly class Rate extends AbstractDataType
{
    public int $value;
    public int $decimals;
    public RateType $rateType;

    public function __construct(string $serializedData)
    {
        $result = $this->_deserialize($serializedData);
        $this->value = $result[0];
        $this->decimals = $result[1];
        $this->rateType = $result[2];
    }

    /**
     * @return array{0:int, 1:int, 2:RateType}
     */
    protected function _deserialize(string $serializedData): array
    {
        $parts = explode(' ', $serializedData);
        if (count($parts) !== 2) {
            throw new DeserializeException('The value must have an rate type % or ‰ seperated by an space', 1696348899);
        }
        $valueString = $parts[0];
        $rateTypeString = $parts[1];
        $rateType = RateType::tryFrom($rateTypeString);
        if ($rateType === null) {
            throw new DeserializeException('The rate type must be % or ‰', 1696348977);
        }
        $numberArray = NumberStringToNumberInt::numberStringToNumberInt($valueString);
        list($value, $decimals) = $numberArray;
        return [
            $value,
            $decimals,
            $rateType,
        ];
    }

    protected function _serializeToString(): string
    {
        $value = $this->value;
        $decimals = $this->decimals;

        $rate = '';
        $valueString = (string) $value;
        if ($decimals > 0) {
            while (strlen($valueString) <= $decimals) {
                $valueString = '0' . $valueString;
            }
            $rate .= substr($valueString, 0, $decimals * -1);
            $rate .= '.';
            $rate .= substr($valueString, $decimals * -1);
        } else {
            $rate .= $valueString;
        }

        $rate .= ' ';
        $rate .= $this->rateType->value;
        return $rate;
    }
}
