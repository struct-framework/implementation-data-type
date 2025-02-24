<?php

declare(strict_types=1);

namespace Struct\DataType;

use function explode;
use function str_starts_with;
use function strlen;
use Struct\Contracts\Operator\SignChangeInterface;
use Struct\Contracts\Operator\SumInterface;
use Struct\DataType\Enum\Currency;
use Struct\DataType\Internal\Helper\NumberStringToNumberInt;
use Struct\Exception\DeserializeException;
use Struct\Exception\Operator\DataTypeException;
use function substr;

final readonly class Amount extends AbstractDataType implements SumInterface, SignChangeInterface
{
    public int $value;
    public int $decimals;
    public Currency $currency;

    public function __construct(string $serializedData)
    {
        $result = $this->_deserialize($serializedData);
        $this->value = $result[0];
        $this->decimals = $result[1];
        $this->currency = $result[2];
    }

    /**
     * @param string $serializedData
     * @return array{0: int, 1: int, 2: Currency}
     */
    protected function _deserialize(string $serializedData): array
    {
        $negative = false;
        if (str_starts_with($serializedData, '-')) {
            $negative = true;
            $serializedData = substr($serializedData, 1);
        }
        $parts = explode(' ', $serializedData);
        if (count($parts) !== 2) {
            throw new DeserializeException(1696314552, 'The amount and currency must be separated by a space');
        }
        $amountString = $parts[0];
        $currencyCode = $parts[1];
        $currency = null;
        $cases = Currency::cases();
        foreach ($cases as $case) {
            if ($case->name === $currencyCode) {
                $currency = $case;
            }
        }
        if ($currency === null) {
            throw new DeserializeException(1696315127, 'The currency code is invalid: ' . $currencyCode);
        }

        $numberArray = NumberStringToNumberInt::numberStringToNumberInt($amountString);
        list($value, $decimals) = $numberArray;

        if ($negative === true) {
            $value *= -1;
        }

        return [$value, $decimals, $currency];
    }

    protected static function createString(int $value, Currency $currency, int $decimals = 2): string
    {
        $negative = false;
        if ($value < 0) {
            $negative = true;
            $value *= -1;
        }
        $amount = '';
        if ($negative === true) {
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
        $amount .= $currency->name;
        return $amount;
    }

    public static function create(int $value, Currency $currency, int $decimals = 2): self
    {
        $string = self::createString($value, $currency, $decimals);
        return new self($string);
    }

    protected function _serializeToString(): string
    {
        return self::createString($this->value, $this->currency, $this->decimals);
    }

    public static function sum(array $summandList): self
    {
        $decimals = 0;
        $currency = null;

        if (count($summandList) === 0) {
            throw new DataTypeException('The summand list is empty', 1696314552);
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
            if ($summand->decimals > $decimals) {
                $decimals = $summand->decimals;
            }
        }
        $sum = 0;
        /** @var self $summand */
        foreach ($summandList as $summand) {
            $tensShift = 10 ** ($decimals - $summand->decimals);
            $value = $summand->value * $tensShift;
            $sum += $value;
        }

        $result = self::create($sum, $currency, $decimals);
        return $result;
    }

    public static function signChange(SignChangeInterface $left): self
    {
        if ($left::class !== self::class) {
            throw new DataTypeException('Must be of type: <' . self::class . '>', 1737449214);
        }
        $amount = self::create($left->value * -1, $left->currency, $left->decimals);
        return $amount;
    }
}
