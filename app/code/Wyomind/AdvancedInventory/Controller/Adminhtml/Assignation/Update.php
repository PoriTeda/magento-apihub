<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\Assignation;

/**
 * Index action
 */
class Update extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Assignation
{

    /**
     * Execute action
     */
    public function execute()
    {
        $assignation = $this->_modelAssignationFactory->create();
        $entityId = $this->getRequest()->getParam('entity_id');
        $data = "";
        parse_str($this->getRequest()->getParam('data'), $data);
        if ($assignation->update($entityId, $data)) {
            $resultPage = $this->_resultPageFactory->create();
            return $resultPage;
        }
    }
}
