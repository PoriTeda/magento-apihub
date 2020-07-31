<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Block\Adminhtml\Promo\SalesRule\Edit\Tab;

class Labels extends \Magento\SalesRule\Block\Adminhtml\Promo\Quote\Edit\Tab\Labels
{
    /**
     * Labels constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\AdminGws\Model\Role\StoreManager $roleStoreManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\AdminGws\Model\Role\StoreManager $roleStoreManager,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $ruleFactory, $data);
        $this->_storeManager = $roleStoreManager;
    }
}
