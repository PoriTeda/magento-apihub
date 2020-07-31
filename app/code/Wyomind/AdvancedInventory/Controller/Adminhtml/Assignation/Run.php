<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\Assignation;

/**
 * Index action
 */
class Run extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Assignation
{

    /**
     * Execute action
     */
    public function execute()
    {

        $assignation = $this->_modelAssignationFactory->create();
        $entityId = $this->getRequest()->getParam('entity_id');
        $data = $assignation->run($entityId);
        if ($data) {
            $this->getResponse()->representJson($this->_objectManager->create('Magento\Framework\Json\Helper\Data')->jsonEncode($data));
        }
    }
}
