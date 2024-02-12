<?php

declare(strict_types=1);

namespace Struct\DataType;

use function explode;
use function str_starts_with;
use function strlen;
use Struct\Contracts\Operator\SignChangeInterface;
use Struct\Contracts\Operator\SumInterface;
use Struct\DataType\Enum\AmountVolume;
use Struct\DataType\Enum\Currency;
use Struct\DataType\Private\Helper\NumberStringToNumberInt;
use Struct\Exception\DeserializeException;
use Struct\Exception\Operator\DataTypeException;
use function substr;

final class Amount extends AbstractDataType implements SumInterface, SignChangeInterface
{
    protected int $value;
    protected Currency $currency = Currency::EUR;
    protected AmountVolume $amountVolume = AmountVolume::Base;
    protected int $decimals = 2;

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getAmountVolume(): AmountVolume
    {
        return $this->amountVolume;
    }

    public function setAmountVolume(AmountVolume $amountVolume): void
    {
        $this->amountVolume = $amountVolume;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    public function setDecimals(int $decimals): void
    {
        $this->decimals = $decimals;
    }

    public function setAmount(
        int $value,
        Currency $currency = Currency::EUR,
        AmountVolume $amountVolume = AmountVolume::Base,
        int $decimals = 2
    ): void {
        $this->value = $value;
        $this->currency = $currency;
        $this->amountVolume = $amountVolume;
        $this->decimals = $decimals;
    }

    protected function _deserializeFromString(string $serializedData): void
    {
        $negativ = false;
        if (str_starts_with($serializedData, '-')) {
            $negativ = true;
            $serializedData = substr($serializedData, 1);
        }

        $parts = explode(' ', $serializedData);
        if (count($parts) !== 2) {
            throw new DeserializeException('The amount and currency must be separated by a space', 1696314552);
        }

        $amountString = $parts[0];
        $currencyCode = $parts[1];

        $amountVolumeCharacter = '';
        if (strlen($currencyCode) === 4) {
            $amountVolumeCharacter = substr($currencyCode, 0, 1);
            $currencyCode = substr($currencyCode, 1);
        }

        $amountVolume = AmountVolume::tryFrom($amountVolumeCharacter);
        $currency = null;
        $cases = Currency::cases();
        foreach ($cases as $case) {
            if ($case->name === $currencyCode) {
                $currency = $case;
            }
        }

        if ($amountVolume === null) {
            throw new DeserializeException('The amount value is invalid: ' . $amountVolumeCharacter, 1696315092);
        }
        if ($currency === null) {
            throw new DeserializeException('The currency code is invalid: ' . $currencyCode, 1696315127);
        }

        $numberArray = NumberStringToNumberInt::numberStringToNumberInt($amountString);
        list($value, $decimals) = $numberArray;

        if ($negativ === true) {
            $value *= -1;
        }

        $this->value = $value;
        $this->decimals = $decimals;
        $this->currency = $currency;
        $this->amountVolume = $amountVolume;
    }

    protected function _serializeToString(): string
    {
        $value = $this->value;
        $decimals = $this->decimals;
        $negativ = false;
        if ($value < 0) {
            $negativ = true;
            $value *= -1;
        }

        $amount = '';
        if ($negativ === true) {
            $amount .= '-';
        }

        $valueString = (string) $value;
        if ($decimals > 0) {
            while (strlen($valueString) <= $decimals) {
                $valueString = '0' . $valueString;
            }
            $amount .= substr($valueString, 0, $decimals * -1);
            $amount .= '.';
            $amount .= substr($valueString, $decimals * -1);
        } else {
            $amount .= $valueString;
        }

        $amount .= ' ';
        $amount .= $this->amountVolume->value;
        $amount .= $this->currency->name;
        return $amount;
    }

    public static function sum(array $summandList): self
    {
        $amountVolume = AmountVolume::Million;
        $decimals = 0;
        $currency = null;

        if (count($summandList) === 0) {
            throw new DataTypeException('There must be at least one summand', 1696344667);
        }

        foreach ($summandList as $summand) {
            if ($summand instanceof self === false) {
                throw new DataTypeException('All summand must be of type: ' . self::class, 1696344427);
            }
            if ($currency === null) {
                $currency = $summand->currency;
            }
            if ($summand->currency !== $currency) {
                throw new DataTypeException('All summand must have the same currency', 1696344461);
            }
            if ($summand->getAmountVolume() === AmountVolume::Base) {
                $amountVolume = AmountVolume::Base;
            }
            if (
                $summand->getAmountVolume() === AmountVolume::Thousand &&
                $amountVolume === AmountVolume::Million
            ) {
                $amountVolume = AmountVolume::Thousand;
            }
            if ($summand->getDecimals() > $decimals) {
                $decimals = $summand->getDecimals();
            }
        }

        $sum = 0;

        /** @var Amount $summand */
        foreach ($summandList as $summand) {
            $tensShift = 10 ** ($decimals - $summand->decimals);

            $volumeShift = 1;
            if (
                $amountVolume === AmountVolume::Base &&
                $summand->getAmountVolume() === AmountVolume::Thousand
            ) {
                $volumeShift = 1000;
            }

            if (
                $amountVolume === AmountVolume::Base &&
                $summand->getAmountVolume() === AmountVolume::Million
            ) {
                $volumeShift = 1000 * 1000;
            }

            if (
                $amountVolume === AmountVolume::Thousand &&
                $summand->getAmountVolume() === AmountVolume::Million
            ) {
                $volumeShift = 1000;
            }

            $value = $summand->value * $tensShift * $volumeShift;
            $sum += $value;
        }

        $result = new self();
        $result->setDecimals($decimals);
        $result->setAmountVolume($amountVolume);
        $result->setCurrency($currency);
        $result->setValue($sum);

        return $result;
    }

    public static function signChange(SignChangeInterface $left): self
    {
        /** @var self $result */
        $result = clone $left;
        $result->setValue($result->getValue() * -1);
        return $result;
    }
}
