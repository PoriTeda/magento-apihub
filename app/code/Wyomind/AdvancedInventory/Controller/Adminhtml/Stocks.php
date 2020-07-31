<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml;

/**
 * Controller for Profile items
 */
abstract class Stocks extends \Magento\Backend\App\Action
{
    /* Object Property */

    protected $_context = null;
    protected $_resultPageFactory = null;
    protected $_coreHelper = null;
    protected $_helperData = null;
    protected $_stockModel = null;
    protected $_itemModel = null;
    protected $_productModel = null;
    protected $_stockRegistry = null;
    protected $_journalHelper = null;
    protected $_permissionsHelper = null;
    protected $_coreRegistry = null;
    protected $_posModel = null;
    public $storeManagerInterface = null;
    public $resultRawFactory = null;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Wyomind\Core\Helper\Data $coreHelper,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Wyomind\AdvancedInventory\Model\Stock $stockModel,
        \Wyomind\AdvancedInventory\Model\Item $itemModel,
        \Wyomind\PointOfSale\Model\PointOfSale $posModel,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Wyomind\AdvancedInventory\Helper\Journal $journalHelper,
        \Wyomind\AdvancedInventory\Helper\Permissions $permissionsHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        $this->_context = $context;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_coreHelper = $coreHelper;
        $this->_helperData = $helperData;
        $this->_stockModel = $stockModel;
        $this->_itemModel = $itemModel;
        $this->_posModel = $posModel;
        $this->_productModel = $productModel;
        $this->_stockRegistry = $stockRegistry;
        $this->_journalHelper = $journalHelper;
        $this->_permissionsHelper = $permissionsHelper;
        $this->_coreRegistry = $coreRegistry;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->resultRawFactory = $resultRawFactory;

        parent::__construct($context);
    }

    /**
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Wyomind_AdvancedInventory::stocks');
    }

    /**
     *
     * @param type $data
     * @return boolean
     */
    protected function _validatePostData($data)
    {
        $errorNo = true;
        if (!empty($data['layout_update_xml']) || !empty($data['custom_layout_update_xml'])) {
            /** @var $validatorCustomLayout \Magento\Core\Model\Layout\Update\Validator */
            $validatorCustomLayout = $this->_objectManager->create('Magento\Core\Model\Layout\Update\Validator');
            if (!empty($data['layout_update_xml']) && !$validatorCustomLayout->isValid($data['layout_update_xml'])) {
                $errorNo = false;
            }
            if (!empty($data['custom_layout_update_xml']) && !$validatorCustomLayout->isValid(
                $data['custom_layout_update_xml']
            )
            ) {
                $errorNo = false;
            }
            foreach ($validatorCustomLayout->getMessages() as $message) {
                $this->messageManager->addError($message);
            }
        }
        return $errorNo;
    }

    abstract public function execute();
}
