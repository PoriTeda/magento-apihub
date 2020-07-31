<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Block\Adminhtml\Catalog\Product\Edit\Tab;

/**
 * For Magento 2.0.x
 */
class AdvancedInventory extends \Magento\Backend\Block\Template
{

    protected $_stockModel;
    protected $_helperCore;
    protected $_helperData;
    protected $_posModel;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Wyomind\AdvancedInventory\Model\Stock $stockModel,
        \Wyomind\Core\Helper\Data $helperCore,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Wyomind\PointOfSale\Model\PointOfSale $posModel,
        array $data = []
    ) {
        $this->_stockModel = $stockModel;
        $this->_helperCore = $helperCore;
        $this->_helperData = $helperData;
        $this->_posModel = $posModel;
        $this->_authorization = $context->getAuthorization();
        parent::__construct($context, $data);
        $this->setTemplate('Wyomind_AdvancedInventory::catalog/product/tab/inventory.phtml');
    }

    public function isAuthorized()
    {
        return $this->_authorization->isAllowed('Wyomind_AdvancedInventory::stocks');
    }

    public function getHelperCore()
    {
        return $this->_helperCore;
    }
    
    public function getHelperData()
    {
        return $this->_helperData;
    }
    
    public function getStockModel()
    {
        return $this->_stockModel;
    }
    
    public function getPosModel()
    {
        return $this->_posModel;
    }
}
