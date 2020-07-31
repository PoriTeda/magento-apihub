<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\Journal;

/**
 * Index action
 */
class Index extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Journal
{

    /**
     * Execute action
     */
    public function execute()
    {


        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu("Wyomind_AdvancedInventory::journal");
        $resultPage->getConfig()->getTitle()->prepend(__('Advanced Inventory > Stock movement journal'));
        $resultPage->addBreadcrumb(__('Advanced Inventory'), __('Advanced Inventory'));
        $resultPage->addBreadcrumb(__('Stock movement journal'), __('Stock movement journal'));

        return $resultPage;
    }
}
