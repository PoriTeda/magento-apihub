<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model;

class Item extends \Magento\Framework\Model\AbstractModel
{

    public function _construct()
    {
        $this->_init('Wyomind\AdvancedInventory\Model\ResourceModel\Item');
    }

    public function loadByProductId($productId)
    {
        $collection = $this->getCollection()
                ->addFieldToFilter('product_id', ['eq' => $productId]);
        return $collection->getFirstItem();
    }
}
