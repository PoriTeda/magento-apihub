<?php

namespace Riki\Promo\Model\Source\SalesRule;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\OfflineShipping\Model\SalesRule\Rule;

class FreeShippingOptions implements OptionSourceInterface
{

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     * @since 100.1.0
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 0,
                'label' => __('No')
            ],
            [
                'value' => Rule::FREE_SHIPPING_ITEM,
                'label' => __('For matching items only')
            ]
        ];
    }
}