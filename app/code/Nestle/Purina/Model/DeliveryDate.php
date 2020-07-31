<?php
/**
 * Nestle Purina Vets
 * PHP version 7
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
namespace Nestle\Purina\Model;

/**
 * Class DeliveryDate
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class DeliveryDate extends \Riki\DeliveryType\Model\DeliveryDate
{
    /**
     * Get list time slot from database
     *
     * @return array|null
     */
    public function getListTimeSlot()
    {
        if ($this->_timeSlot === null) {
            $arrTimeSlot = [];
            $collection = $this->collectionTimeSlot->addOrder(
                "position",
                \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC
            );
            if ($collection->getSize()) {
                $arrTimeSlot[] = [
                    'value' => 0,
                    'label' => __("Unspecified")
                ];
                foreach ($collection->getData() as $data) {
                    $arrTimeSlot[] = [
                        'value' => $data["id"],
                        'label' => $data['slot_name']
                    ];
                }
            }

            $this->_timeSlot = $arrTimeSlot;
        }
        return $this->_timeSlot;
    }

    /**
     * Range of dates
     *
     * @param $nextDate
     * @param $posCode
     * @param array $extendInfo
     * @param null $customAvailableDate
     * @return array
     */
    public function caculateFinalDay($nextDate, $posCode, $extendInfo = [], $customAvailableDate = null)
    {
        $extendInfo = array_merge(
            [
            'firstCal' => true, // first calculate next delivery date
            'now' => $this->timezone->formatDateTime($this->dateZone->gmtDate(), 2),
            ], $extendInfo
        );

        $now = $extendInfo['now'];
        $bufferDate = $extendInfo['firstCal'] ==
        true ? 1 : 1 + $this->helperDelivery->getBufferDate();
        $nextDate = $nextDate + $bufferDate;
                // + 1 buffer ( it's not config of buffer days)
        $dd =  parent::getCalendarPeriod();
        $arrDate = [];
        //$arrDate[] = date('Y-m-d', strtotime($now. " + $nextDate days"));
        for ($i = $nextDate; $i <= $dd; $i++) {
            $nextDay = date('Y-m-d', strtotime($now . ' +' . $i . ' day'));
            // warehouse non working on saturday
            if (date('l', strtotime($nextDay)) == 'Saturday') {
                if ($this->helperDelivery->getHolidayOnSaturday($posCode)) {
                    $dd++;
                }
            }
            // warehouse non working on sunday
            if (date('l', strtotime($nextDay)) == 'Sunday') {
                if ($this->helperDelivery->getHolidayOnSunday($posCode)) {
                    $dd++;
                }
            }
            // this day in list special list holiday of japan
            if ($this->helperDelivery->isSpecialHoliday($posCode, $nextDay)) {
                if (date('l', strtotime($nextDay)) != 'Saturday'
                    && date('l', strtotime($nextDay)) != 'Sunday'
                ) {
                    $dd++;
                }
                if (date('l', strtotime($nextDay)) == 'Saturday'
                    && !$this->helperDelivery->getHolidayOnSaturday($posCode)
                ) {
                    $dd++;
                }
                if (date('l', strtotime($nextDay)) == 'Sunday'
                    && !$this->helperDelivery->getHolidayOnSunday($posCode)
                ) {
                    $dd++;
                }
            }
            $arrDate[] = $nextDay;
        }
        array_pop($arrDate);
        return $arrDate;
    }
}
