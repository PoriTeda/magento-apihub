<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class InitCatalogRuleData implements ObserverInterface
{
    /**
     * @var \Magento\Sales\Model\AdminOrder\Create
     */
    protected $adminOrderCreate;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * InitCatalogRuleData constructor.
     * @param \Magento\Sales\Model\AdminOrder\Create $adminOrderCreate
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Sales\Model\AdminOrder\Create $adminOrderCreate,
        \Magento\Framework\Registry $registry
    ) {
        $this->adminOrderCreate = $adminOrderCreate;
        $this->coreRegistry = $registry;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->coreRegistry->unregister('rule_data');

        $this->adminOrderCreate->initRuleData();
    }
}