<?php
namespace Riki\PurchaseRestriction\Observer;

class SalesOrderCancel implements \Magento\Framework\Event\ObserverInterface
{
    protected $_resourceModel;

    /**
     * @param \Riki\PurchaseRestriction\Model\ResourceModel\PurchaseHistory $resource
     */
    public function __construct(
        \Riki\PurchaseRestriction\Model\ResourceModel\PurchaseHistory $resource
    ){
        $this->_resourceModel = $resource;
    }

    /**
     * delete record after delete order
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        $this->_resourceModel->deleteByOrder($order);
    }
}
