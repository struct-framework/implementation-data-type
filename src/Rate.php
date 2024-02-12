<?php

declare(strict_types=1);

namespace Struct\DataType;

use function strlen;
use Struct\DataType\Enum\RateType;
use Struct\DataType\Private\Helper\NumberStringToNumberInt;
use Struct\Exception\DeserializeException;
use function substr;

final class Rate extends AbstractDataType
{
    protected int $value;
    protected RateType $rateType;
    protected int $decimals;

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    public function getRateType(): RateType
    {
        return $this->rateType;
    }

    public function setRateType(RateType $rateType): void
    {
        $this->rateType = $rateType;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    public function setDecimals(int $decimals): void
    {
        $this->decimals = $decimals;
    }

    public function setRate(
        int $value,
        RateType $rateType = RateType::Percent,
        int $decimals = 2
    ): void {
        $this->value = $value;
        $this->rateType = $rateType;
        $this->decimals = $decimals;
    }

    protected function _deserializeFromString(string $serializedData): void
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

        $this->value = $value;
        $this->rateType = $rateType;
        $this->decimals = $decimals;
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
