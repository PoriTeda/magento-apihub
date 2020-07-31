<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\Assignation;

/**
 * Index action
 */
class View extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Assignation
{

    /**
     * Execute action
     */
    public function execute()
    {

        $resultPage = $this->_resultPageFactory->create();
        return $resultPage;
    }
}
