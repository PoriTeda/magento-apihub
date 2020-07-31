<?php
/**
 * @author Riki Team
 * @copyright Copyright (c) 2016 Riki (https://www.Riki.com)
 * @package Riki_Preorder
 */

/**
 * Copyright © 2016 Riki. All rights reserved.
 */

namespace Riki\Preorder\Model\ResourceModel\OrderItemPreorder;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Riki\Preorder\Model\OrderItemPreorder', 'Riki\Preorder\Model\ResourceModel\OrderItemPreorder');
    }
}