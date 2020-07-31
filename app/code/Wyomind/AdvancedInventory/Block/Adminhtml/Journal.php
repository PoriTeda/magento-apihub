<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Block\Adminhtml;

class Journal extends \Magento\Backend\Block\Widget\Grid\Container
{

    protected function _construct()
    {

        $this->_controller = 'adminhtml_journal';
        $this->_blockGroup = 'Wyomind_AdvancedInventory';
        $this->_headerText = __('Stock Movement Journal');


        parent::_construct();

        $this->removeButton('add');
        $this->removeButton('reset');
    }
}
