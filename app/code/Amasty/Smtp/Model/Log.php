<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */

namespace Amasty\Smtp\Model;

use Magento\Framework\Model\AbstractModel;

class Log extends AbstractModel
{
    const STATUS_SENT    = 0;
    const STATUS_FAILED  = 1;
    const STATUS_PENDING = 2;
    const STATUS_RESEND_FAIL = 3;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Amasty\Smtp\Model\ResourceModel\Log');
    }
}
