<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\Permissions;

/**
 * Index action
 */
class Save extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Permissions
{

    public function execute()
    {


        $this->_helperCore->setDefaultConfig("advancedinventory/system/permissions", $this->getRequest()->getParam('permissions'));
        $this->_cacheManager->clean(['config']);
        $rtn['error'] = false;
        $this->getResponse()->representJson($this->_objectManager->create('Magento\Framework\Json\Helper\Data')->jsonEncode($rtn));
    }
}
