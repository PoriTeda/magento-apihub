<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\GiftWrapping\Model\ResourceModel\Wrapping;

/**
 * Gift Wrapping Collection
 *
 */
class Collection extends \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection
{
    public function toOptionArray()
    {
        return array_merge(
            [['value' => '', 'label' => __('Please select')]],
            $this->_toOptionArray('wrapping_id', 'gift_name')
        );
    }
}
