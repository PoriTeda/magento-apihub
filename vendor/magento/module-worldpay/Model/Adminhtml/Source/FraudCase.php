<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Model\Adminhtml\Source;

class FraudCase implements \Magento\Framework\Option\ArrayInterface
{
    const NOT_SUPPORTED = '0';

    const NOT_CHECKED = '1';

    const MATCHED = '2';

    const NOT_MATCHED = '4';

    const PARTIALLY_MATCHED  = '8';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '',
                'label' => __('None')
            ],
            [
                'value' => self::NOT_SUPPORTED,
                'label' => __('Not supported')
            ],
            [
                'value' => self::NOT_CHECKED,
                'label' => __('Not checked')
            ],
            [
                'value' => self::NOT_MATCHED,
                'label' => __('Not matched')
            ],
            [
                'value' => self::PARTIALLY_MATCHED,
                'label' => __('Partially matched')
            ]
        ];
    }
}
