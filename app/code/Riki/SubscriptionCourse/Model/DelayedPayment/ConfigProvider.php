<?php

namespace Riki\SubscriptionCourse\Model\DelayedPayment;

class ConfigProvider
{
    const ALLOWED_PAYMENT_METHOD_CODES = [\Bluecom\Paygent\Model\Paygent::CODE];

    /** 3 months, 4 months, 6 months  */
    const ALLOWED_FREQUENCY_INTERVALS = [1, 2, 3, 4, 5, 6];

    const ALLOWED_FREQUENCY_UNIT = 'month';

    /**
     * @var \Riki\SubscriptionFrequency\Helper\Data
     */
    protected $frequencyHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * ConfigProvider constructor.
     * @param \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->frequencyHelper = $frequencyHelper;
    }

    /**
     * Get list array allow Frequency of Delay Payment
     * default is 3 months and 4 months
     *
     * @return array
     */
    public function getAllowedFrequencies()
    {
        $allowedFrequencies = [];
        /** 3 months, 4 months */
        $listFrequency = $this->frequencyHelper->getFrequencyByIntervalAndUnit(
            self::ALLOWED_FREQUENCY_INTERVALS,
            self::ALLOWED_FREQUENCY_UNIT
        );

        foreach ($listFrequency as $frequency) {
            $allowedFrequencies[] = $frequency['frequency_id'];
        }

        return $allowedFrequencies;
    }
}
