<?php

namespace Riki\Rule\Helper;

use Riki\Rule\Model\OrderSapBooking;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ORDER_SUBSCRIPTION = 'SUBSCRIPTION';

    const ORDER_SPOT = 'SPOT';

    const SALESRULE_SUBSCRIPTION = 1;

    const SALESRULE_SPOT = 0;

    const CATALOGRULE_SUBSCRIPTION = 2;

    const CATALOGRULE_SPOT = 1;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $_date;

    public function __construct(\Magento\Framework\Stdlib\DateTime\TimezoneInterface $date)
    {
        $this->_date = $date;
    }

    /**
     * @param \Magento\Framework\DataObject $dataObject
     * @param $validateResult
     * @return array|bool
     * @throws \Exception
     */
    public function validateDatetime(\Magento\Framework\DataObject $dataObject, $validateResult)
    {
        if ($dataObject->getFromTime() && $dataObject->getToTime()
            && $validateResult === true
        ) {
            $fromTime = $dataObject->getFromTime();
            $toTime =  $dataObject->getToTime();
            $fromDateTime = new \DateTime($fromTime);
            $toDateTime = new \DateTime($toTime);
            if ($fromDateTime > $toDateTime) {
                if ($validateResult === true) {
                    $validateResult = [];
                }
                $validateResult[] = __('End Date time must follow Start Date time.');
            }
        }

        if ($dataObject->getFromTime() && $validateResult === true) {
            $fromDateTime = $dataObject->getFromTime();
            $now = $this->_date->date()->format('Y-m-d 00:00:00');
            if ($fromDateTime < $now) {
                if ($validateResult === true) {
                    $validateResult = [];
                }
                $validateResult[] = __('Start date and End date have to larger or equal the current date.');
            }
        }

        return $validateResult;
    }

    public function formatTime($time)
    {
        if (is_array($time) && sizeof($time) == 3) {
            $time = implode(':', $time);
        } else {
            $time = '00:00:00';
        }

        return $time;
    }

    /**
     * Validate free gift
     * - The "Promo item / Free gift WBS code" will be stored at item level for this promo item (RIKI-2861)
     *
     * @param \Magento\Sales\Model\Order\Item $item Item
     * @param string                          $type string
     *
     * @return bool
     */
    public function validateSapType($item, $type)
    {
        if ($type === OrderSapBooking::SAP_TYPE_FREE_GIFT) {
            if (!$item->getPrice() && \Zend_Validate::is($item->getName(), 'Regex', ['pattern' => '/^GIFTFREE/'])) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate order type and catalog rule
     *
     * @param \Magento\Quote\Model\Quote      $quote Quote
     * @param \Magento\Sales\Model\Order      $order Order
     * @param \Magento\CatalogRule\Model\Rule $rule  Rule
     *
     * @return bool
     */
    public function validateRule($quote, $order, $rule)
    {
        if ($order->getRikiType() == self::ORDER_SUBSCRIPTION ||
            $order->getRikiType() == \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT
        ) {
            if ($rule->getSubscription() == self::CATALOGRULE_SPOT) {
                // Subscription Order and Rule apply SPOT only
                return false;
            } else {
                // check rule can apply for subscription
                if (!$this->isApplyToSubscription($quote, $rule)) {
                    return false;
                }
            }
        } else {
            if ($rule->getSubscription() == self::CATALOGRULE_SUBSCRIPTION) {
                // SPOT Order and Rule apply Subscription only
                return false;
            }
        }

        return true;
    }

    /**
     * Check this rule can apply to this quote
     * We have to check this because catalog rule saved in database table
     * When run reindex
     *
     * @param   \Magento\Quote\Model\Quote      $quote Quote
     * @param   \Magento\CatalogRule\Model\Rule $rule  Rule
     *
     * @return  bool
     */
    public function isApplyToSubscription($quote, $rule)
    {
        $ruleId = $rule->getId();
        $ruleCourseIds = $rule->getResource()->getSubscriptionCourseIds($ruleId);
        $ruleFrequencyIds = $rule->getResource()->getSubscriptionFrequencyIds($ruleId);

        $courseId = $quote->getData('riki_course_id');
        $frequencyId = $quote->getData('riki_frequency_id');

        if (in_array($courseId, $ruleCourseIds) && in_array($frequencyId, $ruleFrequencyIds)) {
            return true;
        }
        return false;
    }
}