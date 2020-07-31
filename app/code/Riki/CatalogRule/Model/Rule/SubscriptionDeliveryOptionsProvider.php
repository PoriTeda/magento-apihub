<?php
namespace Riki\CatalogRule\Model\Rule;

class SubscriptionDeliveryOptionsProvider implements \Magento\Framework\Option\ArrayInterface
{
    const SUBSCRIPTION_DELIVERY_EVERY_N = 1;
    const SUBSCRIPTION_DELIVERY_ON_N = 2;
    const SUBSCRIPTION_DELIVERY_ALL = 3;
    const SUBSCRIPTION_DELIVERY_FROM_N = 4;

    /**
     * Get label of option
     *
     * @param $option
     * @return \Magento\Framework\Phrase
     */
    public function getLabel($option)
    {
        switch ($option) {
            case self::SUBSCRIPTION_DELIVERY_FROM_N:
                return __('From N delivery');

            case self::SUBSCRIPTION_DELIVERY_EVERY_N:
                return __('Every N delivery');

            case self::SUBSCRIPTION_DELIVERY_ON_N:
                return __('On N delivery');

            case self::SUBSCRIPTION_DELIVERY_ALL:
                return __('All deliveries');

            default:
                return $option;
        }
    }

    /**
     * To array ['label' => label, 'value' => value]
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => $this->getLabel(self::SUBSCRIPTION_DELIVERY_ALL),
                'value' => self::SUBSCRIPTION_DELIVERY_ALL
            ],
            [
                'label' => $this->getLabel(self::SUBSCRIPTION_DELIVERY_EVERY_N),
                'value' => self::SUBSCRIPTION_DELIVERY_EVERY_N
            ],
            [
                'label' => $this->getLabel(self::SUBSCRIPTION_DELIVERY_ON_N),
                'value' => self::SUBSCRIPTION_DELIVERY_ON_N
            ]
            ,
            [
                'label' => $this->getLabel(self::SUBSCRIPTION_DELIVERY_FROM_N),
                'value' => self::SUBSCRIPTION_DELIVERY_FROM_N
            ]

        ];
    }

    /**
     * To array [value => label]
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::SUBSCRIPTION_DELIVERY_ALL => $this->getLabel(self::SUBSCRIPTION_DELIVERY_ALL),
            self::SUBSCRIPTION_DELIVERY_EVERY_N => $this->getLabel(self::SUBSCRIPTION_DELIVERY_EVERY_N),
            self::SUBSCRIPTION_DELIVERY_ON_N => $this->getLabel(self::SUBSCRIPTION_DELIVERY_ON_N),
            self::SUBSCRIPTION_DELIVERY_FROM_N => $this->getLabel(self::SUBSCRIPTION_DELIVERY_FROM_N),
        ];
    }
}