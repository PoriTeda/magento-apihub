<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Block\Adminhtml\Catalog\Product\Edit\Tab;

class Inventory
{

    public function afterToHtml(
        $subject,
        $rtn
    ) {
        $template = $subject->getLayout()->createBlock("\Wyomind\AdvancedInventory\Block\Adminhtml\Catalog\Product\Edit\Tab\AdvancedInventory")->toHtml();
        return $rtn . $template;
    }
}
