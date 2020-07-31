<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\Permissions;

/**
 * Index action
 */
class Index extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Permissions
{

    /**
     * Execute action
     */
    public function execute()
    {


        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu("Wyomind_AdvancedInventory::permissions");
        $resultPage->getConfig()->getTitle()->prepend(__('Advanced Inventory > Manage permissions'));
        $resultPage->addBreadcrumb(__('Advanced Inventory'), __('Advanced Inventory'));
        $resultPage->addBreadcrumb(__('Manage permissions'), __('Manage permissions'));

        return $resultPage;
    }
}
