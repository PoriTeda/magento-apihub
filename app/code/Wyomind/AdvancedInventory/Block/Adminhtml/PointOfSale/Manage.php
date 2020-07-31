<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Block\Adminhtml\PointOfSale;

class Manage
{

    protected $_helperPermissions;

    public function __construct(
        \Wyomind\AdvancedInventory\Helper\Permissions $helperPermissions
    ) {
        $this->_helperPermissions = $helperPermissions;
    }

    public function after_construct($subject, $return)
    {
        if (!$this->_helperPermissions->hasAllPermissions()) {
            $subject->removeButton("add");
            $subject->removeButton("import");
            $subject->removeButton("export");
        }
        return $return;
    }
}
