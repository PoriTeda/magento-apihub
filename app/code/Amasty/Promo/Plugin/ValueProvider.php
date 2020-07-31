<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Plugin;

use Magento\SalesRule\Model\Rule\Metadata\ValueProvider as SalesRuleValueProvider;

class ValueProvider
{
    /**
     * @param SalesRuleValueProvider $subject
     * @param array $result
     *
     * @return array
     */
    public function afterGetMetadataValues(
        SalesRuleValueProvider $subject,
        array $result
    ) {
        $actions = &$result['actions']['children']['simple_action']['arguments']['data']['config']['options'];
        $autoAddActions = [
            [
                'label' => __('Auto add promo items with products'),
                'value' => 'ampromo_items'
            ],
            [
                'label' => __('Auto add promo items for the whole cart'),
                'value' => 'ampromo_cart'
            ],
            [
                'label' => __('Auto add the same product'),
                'value' => 'ampromo_product'
            ],
            [
                'label' => __('Auto add promo items for every $X spent'),
                'value' => 'ampromo_spent'
            ]
        ];

        $actions[] = [
            'label' => __('Automatically add products to cart'),
            'value' => $autoAddActions
        ];

        return $result;
    }
}
