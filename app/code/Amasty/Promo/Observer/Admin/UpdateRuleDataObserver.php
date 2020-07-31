<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Observer\Admin;

use Magento\Framework\Event\ObserverInterface;

class UpdateRuleDataObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry
    )
    {
        $this->_objectManager = $objectManager;
        $this->_coreRegistry = $registry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $salesrule = $this->_coreRegistry->registry('ampromo_current_salesrule');

        if (!$salesrule)
            return;

        $ampromoData = $observer->getRequest()->getParam('ampromorule');

        if ($salesrule->getId() && $ampromoData) {
            $ampromoRule = $this->_objectManager->create('Amasty\Promo\Model\Rule');

            $ampromoRule
                ->load($salesrule->getId(), 'salesrule_id')
                ->addData($ampromoData)
                ->setData('salesrule_id', $salesrule->getId())
                ->save()
            ;
        }
    }
}
