<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Helper;

class Assignation extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_helperCore;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Wyomind\Core\Helper\Data $helperCore
    ) {
        $this->_helperCore = $helperCore;
        parent::__construct($context);
    }

    public function isUpdatable($status)
    {
        $disallowed = $this->_helperCore->getStoreConfig("advancedinventory/settings/disallow_assignation_status");
        return !in_array($status, explode(',', $disallowed));
    }
}
