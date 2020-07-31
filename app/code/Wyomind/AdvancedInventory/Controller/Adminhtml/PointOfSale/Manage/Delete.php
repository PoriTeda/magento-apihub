<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\PointOfSale\Manage;

class Delete
{

    protected $_messageManager;
    protected $_modelStockFactory = null;

    public function __construct(
        \Wyomind\AdvancedInventory\Model\StockFactory $modelStockFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {

        $this->_messageManager = $messageManager;
        $this->_modelStockFactory = $modelStockFactory;
    }

    public function afterExecute(
        $subject,
        $return
    ) {

        try {
            if ($this->_modelStockFactory->create()->reindex()) {
                $this->_messageManager->addSuccess(__('Index updated `advancedinventory_index`'));
            }
            $this->_modelStockFactory->create()->reindex();
            $this->_messageManager->addSuccess(__('Index updated `advancedinventory_index`'));
        } catch (\Exception $e) {
            $this->_messageManager->addError(__('Error while creating advancedinventory_index') . '<br/><br/>' . $e->getMessage());
        }
        return $return;
    }
}
