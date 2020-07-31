<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\Journal;

class Purge extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Journal
{

    public function execute()
    {
        
        $ids = $this->getRequest()->getParam('ids');
        if ($ids) {
            try {
                foreach ($ids as $id) {
                    $this->_journalModel->setId($id);
                    $this->_journalModel->delete();
                }
                $this->messageManager->addSuccess(__('Journal deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $this->resultRedirectFactory->create()->setPath('advancedinventory/journal/index');
    }
}
