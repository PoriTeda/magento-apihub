<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class UpdateOrderAddress implements ObserverInterface
{

    protected $_salesAddressHelper;

    protected $_orderItemCollection;

    public function __construct(
        \Riki\Sales\Helper\Address $addressHelper,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory
    ){
        $this->_salesAddressHelper = $addressHelper;
        $this->_orderItemCollection = $orderItemCollectionFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $addressId = $observer->getAddressId();

        $orderItemIds = $this->_salesAddressHelper->getOrderItemIdByOrderAddressId($addressId);

        $orderItemCollection = $this->_orderItemCollection->create();
        $orderItemCollection->addFieldToFilter('item_id', ['in' =>  $orderItemIds]);

        foreach($orderItemCollection as $item){
            $item->setDeliveryDate(null);
            $item->setDeliveryNextDeliveryDate(null);
            $item->setDeliveryTime(null);
            $item->setDeliveryTimeslotId(null);
            $item->setDeliveryTimeslotFrom(null);
            $item->setDeliveryTimeslotTo(null);
            $item->save();
        }

        return $this;
    }
}