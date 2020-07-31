<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ConnectionType provides source for backend connection_type selector
 */
class ConnectionType implements ArrayInterface
{
    const CONNECTION_TYPE_DIRECT = 'direct';
    const CONNECTION_TYPE_SHARED = 'shared';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::CONNECTION_TYPE_DIRECT,
                'label' => 'Direct connection',
            ],
            [
                'value' => self::CONNECTION_TYPE_SHARED,
                'label' => 'Responsive shared page'
            ]
        ];
    }
}
