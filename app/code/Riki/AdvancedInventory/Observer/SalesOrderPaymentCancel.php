<?php
namespace Riki\AdvancedInventory\Observer;

class SalesOrderPaymentCancel implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Wyomind\AdvancedInventory\Model\Assignation
     */
    protected $modelAssignation;

    /**
     * SalesOrderPaymentCancel constructor.
     * @param \Wyomind\AdvancedInventory\Model\Assignation $modelAssignation
     */
    public function __construct(
        \Wyomind\AdvancedInventory\Model\Assignation $modelAssignation
    ) {
        $this->modelAssignation = $modelAssignation;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Magento\Framework\Event\Observer|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $this->modelAssignation->cancel($order);
    }
}
