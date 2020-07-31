<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Sales\Block\Dashboard;

/**
 * Sales purchase history block
 */

class Purchasehistory extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * @var \Riki\Sales\Model\ProductPurchaseHistory
     */
    protected $_productPurchaseHistory;
    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $_currentCustomer;
    /** @var \Magento\Customer\Helper\View */
    protected $_helperView;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Helper\View $helperView,
        \Riki\Sales\Model\ProductPurchaseHistory $productPurchaseHistory,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_productPurchaseHistory = $productPurchaseHistory;
        $this->_currentCustomer = $currentCustomer;
        $this->_helperView = $helperView;
        parent::__construct($context, $data);
    }

    /**
     * @param bool $nextStep
     * @return array|null
     */
    public function getListProductPurchaseHistory($nextStep = false)
    {
        $page =1;
        return $this->_productPurchaseHistory->getListProductPurchaseHistory($page, $nextStep);
    }

    /**
     * @return string
     */
    public function getProductUrl()
    {
        return $this->getUrl('sales/dashboard/purchasehistory');
    }

    /**
     * Returns the Magento Customer Model for this block
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer()
    {     
        try {
            return $this->_currentCustomer->getCustomer();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Get the full name of a customer
     *
     * @return string full name
     */
    public function getName()
    {
        return $this->_helperView->getCustomerName($this->getCustomer());
    }

}