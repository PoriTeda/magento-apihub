<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */

namespace Amasty\Smtp\Model;

use Magento\Framework\Model\AbstractModel;

class Debug extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Amasty\Smtp\Model\ResourceModel\Debug');
    }
}
