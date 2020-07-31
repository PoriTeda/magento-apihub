<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model;

class PointOfSale
{

    public function aroundGetPlaces(
        $subject,
        $proceed
    ) {
        $subject = $proceed();
        $subject->addFieldToFilter('manage_inventory', ["eq" => 1]);
        return $subject;
    }
}
