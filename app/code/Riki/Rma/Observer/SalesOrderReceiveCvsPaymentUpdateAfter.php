<?php

namespace Riki\Rma\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesOrderReceiveCvsPaymentUpdateAfter implements ObserverInterface
{
    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory  */
    protected $_orderCollection;

    /** @var \Riki\Rma\Model\ResourceModel\Grid  */
    protected $_rmaGridResource;

    protected $_logger;

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection
     * @param \Riki\Rma\Model\ResourceModel\Grid $rmaGridResource
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Riki\Rma\Model\ResourceModel\Grid $rmaGridResource,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->_orderCollection = $orderCollection;
        $this->_rmaGridResource = $rmaGridResource;
        $this->_logger = $logger;    }

    /**
     *
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderIds = $observer->getOrderIds();

        if(is_array($orderIds)){

            try{
                $this->_rmaGridResource->updatePaymentStatusByOrderId(\Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED, $orderIds);
            }catch (\Exception $e){
                $this->_logger->critical($e);
            }
        }

        return $this;
    }
}
