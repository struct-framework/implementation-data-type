<?php

declare(strict_types=1);

namespace Struct\DataType;


use Struct\Exception\DeserializeException;

final class Period extends AbstractDataType
{

    protected Date $startDate;
    protected ?Date $endDate = null;

    #[\Override]
    protected function _deserializeFromString(string $serializedData): void
    {
        $length = strlen($serializedData);
        if(
            $length === 13 &&
            str_ends_with($serializedData, ' ->') === true
        ) {
            $this->startDate->deserializeFromString(substr($serializedData, 0, 10));
            $this->endDate = null;
            return;
        }
        if($length === 4) {
            $year = (int) $serializedData;
            $this->startDate->setDate($year, 1 ,1);
            $this->endDate->setDate($year, 12 ,31);
            return;
        }
        if($length === 7) {
            $year = (int) substr($serializedData,0,4);
            $month = (int) substr($serializedData,5,2);
            $this->startDate->setDate($year, $month ,1);
            $this->endDate->setDate($year, $month ,1);
            $this->endDate = $this->endDate->lastDayOfTheYear();
            return;
        }
        if($length === 23) {
            $this->startDate->deserializeFromString(substr($serializedData, 0, 10));
            $this->endDate->deserializeFromString(substr($serializedData,-10));
            return;
        }
        throw new DeserializeException('Can not deserialize period: ' . $serializedData, 1724311020);
    }

    #[\Override]
    protected function _serializeToString(): string
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        if($endDate === null) {
            return $this->startDate->serializeToString() . ' ->';
        }
        if(
            $startDate->isFirstDayOfMonth() !== true ||
            $endDate->isLastDayOfMonth() !== true
        ) {
            return $this->_buildCustomSerializeToString();
        }

        if($startDate->getYear() === $endDate->getYear()) {
            if($startDate->getMonth() === $endDate->getMonth()) {
                return $startDate->toMonth()->serializeToString();
            }
            if(
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

    public function setStartDate(Date $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): ?Date
    {
        return $this->endDate;
    }

    public function setEndDate(?Date $endDate): void
    {
        $this->endDate = $endDate;
    }
}
