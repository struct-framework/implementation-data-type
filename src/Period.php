<?php

declare(strict_types=1);

namespace Struct\DataType;

use Struct\Exception\DeserializeException;

final readonly class Period extends AbstractDataType

{
    protected Date $startDate;
    protected ?Date $endDate;

    #[\Override]
    protected function _deserializeFromString(string $serializedData): void
    {
        $length = strlen($serializedData);
        if (
            $length === 13 &&
            str_ends_with($serializedData, ' ->') === true
        ) {
            $this->startDate = new Date(substr($serializedData, 0, 10));
            $this->endDate = null;
            return;
        }
        if ($length === 4) {
            $year = (int) $serializedData;
            $this->startDate = Date::createByYearMonthDay($year, 1, 1);
            $this->endDate = Date::createByYearMonthDay($year, 12, 31);
            return;
        }
        if ($length === 7) {
            $year = (int) substr($serializedData, 0, 4);
            $month = (int) substr($serializedData, 5, 2);
            $this->startDate = Date::createByYearMonthDay($year, $month, 1);
            $this->endDate = Date::createByYearMonthDay($year, 12, 31);
            return;
        }
        if ($length === 23) {
            $this->startDate = new Date(substr($serializedData, 0, 10));
            $this->endDate = new Date(substr($serializedData, -10));
            return;
        }
        throw new DeserializeException('Can not deserialize period: ' . $serializedData, 1724311020);
    }

    #[\Override]
    protected function _serializeToString(): string
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        if ($endDate === null) {
            return $this->startDate->serializeToString() . ' ->';
        }
        if (
            $startDate->isFirstDayOfMonth() !== true ||
            $endDate->isLastDayOfMonth() !== true
        ) {
            return $this->_buildCustomSerializeToString();
        }

        if ($startDate->getYear() === $endDate->getYear()) {
            if ($startDate->getMonth() === $endDate->getMonth()) {
                return $startDate->toMonth()->serializeToString();
            }
            if (
                $startDate->getMonth() === 1 &&
                $endDate->getMonth() === 12
            ) {
                return $startDate->toYear()->serializeToString();
            }
        }

        return $this->_buildCustomSerializeToString();
    }

    protected function _buildCustomSerializeToString(): string
    {
        return $this->startDate->serializeToString() . ' - ' . $this->endDate->serializeToString();
    }

    public function getStartDate(): Date
    {
        return $this->startDate;
    }

    public function getEndDate(): ?Date
    {
        return $this->endDate;
    }
}
