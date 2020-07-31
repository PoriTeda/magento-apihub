<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\Sales;

/**
 * Index action
 */
class Ignore extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Sales
{

    /**
     * Execute action
     */
    public function execute()
    {

        $this->_session->setData('selected_ids', false);
        $this->_redirect('sales/order/index');
    }
}
