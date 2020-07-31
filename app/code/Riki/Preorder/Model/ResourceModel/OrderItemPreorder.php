<?php
/**
 * @author Riki Team
 * @copyright Copyright (c) 2016 Riki (https://www.Riki.com)
 * @package Riki_Preorder
 */

/**
 * Copyright © 2016 Riki. All rights reserved.
 */

namespace Riki\Preorder\Model\ResourceModel;


class OrderItemPreorder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_preorder_order_item_preorder', 'id');
    }
}