<?php

namespace Riki\SubscriptionFrequency\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    protected $frequencyFactory;

    public function __construct(
        Context $context,
        \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory
    ) {
        parent::__construct($context);

        $this->frequencyFactory = $frequencyFactory;
    }

    public function getFrequencyString($frequency)
    {
        if (is_string($frequency)) {
            $frequency = $this->frequencyFactory->create()->load($frequency);
        }

        if ($frequency && $frequency->getId()) {
            return $this->formatFrequency($frequency->getFrequencyInterval(), $frequency->getFrequencyUnit());
        }

        return '';
    }

    public function formatFrequency($interval, $unit)
    {
        return sprintf(__($this->_getFrequencyFormat($interval, $unit)), $interval);
    }

    protected function _getFrequencyFormat($interval, $unit)
    {
        return sprintf('%%s %s%s', $unit, $interval > 1 ? 's' : '');
    }

    /**
     * Get Frequency by interval and unit
     *
     * @param $interval
     * @param $unit
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getFrequencyByIntervalAndUnit($interval, $unit)
    {
        $frequency = $this->frequencyFactory->create()->getCollection()
        ->addFieldToFilter('frequency_interval', ['in' => $interval])
        ->addFieldToFilter('frequency_unit', $unit);

        return $frequency;
    }
}
