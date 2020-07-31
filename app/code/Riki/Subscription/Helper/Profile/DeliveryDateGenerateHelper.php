<?php

namespace Riki\Subscription\Helper\Profile;


class DeliveryDateGenerateHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var array
     */
    protected $nthWeekdayOfMonth = [
        1 => 'first',
        2 => 'second',
        3 => 'third',
        4 => 'fourth',
        5 => 'last'
    ];

    /**
     * Get last date of month
     *
     * @param null $month
     * @param null $year
     * @return false|string
     */
    public function getLastday($month = null, $year = null)
    {
        if (empty($month)) {
            $month = date('m');
        }
        if (empty($year)) {
            $year = date('Y');
        }
        $result = strtotime("{$year}-{$month}-01");
        $result = strtotime('-1 second', strtotime('+1 month', $result));
        return date('Y-m-d', $result);
    }

    /**
     * Get last date of month
     *
     * @param $nextDeliveryDate
     * @param $dateTmp
     * @return false|string
     */
    public function getLastDateOfMonth($nextDeliveryDate, $dateTmp)
    {
        if ($dateTmp != null) {
            $dayTmp = (int)date('d', strtotime($dateTmp));
            $dayNextDeliveryDate = (int)date('d', strtotime($nextDeliveryDate));
            if ($dayNextDeliveryDate < $dayTmp) {
                $monthNextDeliveryDate = (int)date('m', strtotime($nextDeliveryDate));
                $yearNextDeliveryDate = (int)date('Y', strtotime($nextDeliveryDate));
                if ($dayNextDeliveryDate < 4) {
                    return $this->getLastday(($monthNextDeliveryDate - 1), $yearNextDeliveryDate);
                } elseif ($dayNextDeliveryDate < $dayTmp) {
                    $lastDate = $this->getLastday($monthNextDeliveryDate, $yearNextDeliveryDate);
                    $maxDayLastDate = (int)date('d', strtotime($lastDate));
                    if ($dayTmp > $dayNextDeliveryDate) {
                        if ($dayTmp > $maxDayLastDate) {
                            return $yearNextDeliveryDate . '-' . $monthNextDeliveryDate . '-' . $maxDayLastDate;
                        } else {
                            return $yearNextDeliveryDate . '-' . $monthNextDeliveryDate . '-' . $dayTmp;
                        }
                    }
                }
            }
        }
        return $nextDeliveryDate;
    }

    /**
     * Check delivery date
     *
     * @param $deliveryDateTmp
     * @param $nextDeliveryDate
     * @return null
     */
    public function checkDeliveryDate($deliveryDateTmp, $nextDeliveryDate)
    {
        $dayDeliveryDateTmp = (int)date('d', strtotime($deliveryDateTmp));
        $dayNextDeliveryDate = (int)date('d', strtotime($nextDeliveryDate));
        if ($dayDeliveryDateTmp > 28 && $dayNextDeliveryDate >= $dayDeliveryDateTmp) {
            return $deliveryDateTmp;
        }
        return null;
    }

    /**
     * Convert date string (Y-m-d) to date object
     *
     * @param $deliveryDate
     * @return \DateTime
     */
    public function convertDateStringToDateObject($deliveryDate)
    {
        if (!is_numeric($deliveryDate) || $deliveryDate>0) {
            $deliveryDate = strtotime($deliveryDate);
        }

        $deliveryDateObject = new \DateTime();
        $deliveryDateObject->setTimestamp($deliveryDate);
        return $deliveryDateObject;
    }

    /**
     * Get last date of delivery with frequency unit month
     *
     * @param $frequencyUnit
     * @param $nextDeliveryDate
     * @param $dateTmp
     * @return \DateTime|false|string
     */
    public function getDeliveryDateWithFrequencyUnitMonth($frequencyUnit, $nextDeliveryDate, $dateTmp)
    {
        $result = $nextDeliveryDate;
        if ($frequencyUnit=='month') {
            $result = $this->getLastDateOfMonth($nextDeliveryDate, $dateTmp);
        }
        $result = $this->convertDateStringToDateObject($result);
        return $result;
    }

    /**
     * Calculator last day of month
     * @param $nextDeliveryDate
     * @return bool
     */
    public function canCalculatorLastDayOfMonth($nextDeliveryDate)
    {
        $dayNextDeliveryDate = (int)date('d', strtotime($nextDeliveryDate));
        if ($dayNextDeliveryDate > 28) {
            return true;
        }
        return false;
    }

    /**
     * Calculate the nth weekday of month
     *
     * @param string $day
     * @return int
     */
    public function calculateNthWeekdayOfMonth($day)
    {
        return ceil(date('j', strtotime($day)) / 7);
    }

    /**
     * Get next delivery date for special case.
     * Subscription course setup with Next Delivery Date Calculation Option = "day of the week"
     * AND interval_unit="month"
     * AND not Stock Point
     *
     * @param string $nextDeliveryDate
     * @param string $dayOfWeek
     * @param int $nthWeekdayOfMonth
     * @return string $newNextDeliveryDate
     */
    public function getDeliveryDateForSpecialCase($nextDeliveryDate, $dayOfWeek, $nthWeekdayOfMonth)
    {
        $newNextDeliveryDate = $nextDeliveryDate;
        $monthYear = date('Y-m', strtotime($nextDeliveryDate));

        if (isset($this->nthWeekdayOfMonth[$nthWeekdayOfMonth])) {
            $newNextDeliveryDate = date(
                'Y-m-d',
                strtotime($this->nthWeekdayOfMonth[$nthWeekdayOfMonth] . " $dayOfWeek of $monthYear")
            );
        }

        return $newNextDeliveryDate;
    }
}
