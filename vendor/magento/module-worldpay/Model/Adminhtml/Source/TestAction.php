<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Model\Adminhtml\Source;

/**
 * Class TestAction
 */
class TestAction implements \Magento\Framework\Option\ArrayInterface
{
    const REFUSED = 'REFUSED';

    const AUTHORISED = 'AUTHORISED';

    const ERROR = 'ERROR';

    const CAPTURED = 'CAPTURED';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::REFUSED,
                'label' => __('Refused')
            ],
            [
                'value' => self::AUTHORISED,
                'label' => __('Authorised')
            ],
            [
                'value' => self::ERROR,
                'label' => __('Error')
            ],
            [
                'value' => self::CAPTURED,
                'label' => __('Captured')
            ],
        ];
    }
}
