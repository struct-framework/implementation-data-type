<?php

declare(strict_types=1);

namespace Struct\DataType;

use Struct\Exception\DeserializeException;

final readonly class Period extends AbstractDataType
{
    public Date $startDate;
    public ?Date $endDate;

    public function __construct(string $serializedData)
    {
        $result = $this->_deserialize($serializedData);
        $this->startDate = $result[0];
        $this->endDate = $result[1];
    }

    /**
     * @return array{0:Date, 1:Date|null}
     */
    protected function _deserialize(string $serializedData): array
    {
        $startDate = null;
        $endDate = null;
        $length = strlen($serializedData);
        if (
            $length === 13 &&
            str_ends_with($serializedData, ' ->') === true
        ) {
            $startDate = new Date(substr($serializedData, 0, 10));
        }
        if ($length === 4) {
            $year = (int) $serializedData;
            $startDate = new Date($year, 1, 1);
            $endDate = new Date($year, 12, 31);
        }
        if ($length === 7) {
            $year = (int) substr($serializedData, 0, 4);
            $month = (int) substr($serializedData, 5, 2);
            $startDate =  new Date($year, $month, 1);
            $endDate =  new Date($year, 12, 31);
        }
        if ($length === 23) {
            $startDate = new Date(substr($serializedData, 0, 10));
            $endDate = new Date(substr($serializedData, -10));
        }
        if ($startDate === null) {
            throw new DeserializeException(1740344626, 'Can not deserialize period: ' . $serializedData);
        }
        return [
            $startDate,
            $endDate,
        ];
    }

    protected function _serializeToString(): string
    {
        if ($this->endDate === null) {
            return $this->startDate->serializeToString() . ' ->';
        }
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        if (
            $startDate->isFirstDayOfMonth() !== true ||
            $endDate->isLastDayOfMonth() !== true
        ) {
            return $this->_buildCustomSerializeToString($startDate, $endDate);
        }
        if ($startDate->year === $endDate->year) {
            if ($startDate->month === $endDate->month) {
                return $startDate->toMonth()->serializeToString();
            }
            if (
                $startDate->month === 1 &&
                $endDate->month === 12
            ) {
                return $startDate->toYear()->serializeToString();
            }
        }
        return $this->_buildCustomSerializeToString($startDate, $endDate);
    }

    protected function _buildCustomSerializeToString(Date $startDate, Date $endDate): string
    {
        return $startDate->serializeToString() . ' - ' . $endDate->serializeToString();
    }
}
