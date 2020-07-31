<?php
namespace Riki\PurchaseRestriction\Observer;

/**
 * Added new records to purchase history table
 */

class CheckoutSubmitAllAfter implements \Magento\Framework\Event\ObserverInterface
{
    protected $_purchasedHistoryResourceModel;

    protected $_logger;

    /**
     * @param \Riki\PurchaseRestriction\Model\ResourceModel\PurchaseHistory $purchaseHistory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Riki\PurchaseRestriction\Model\ResourceModel\PurchaseHistory $purchaseHistory,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->_purchasedHistoryResourceModel = $purchaseHistory;
        $this->_logger = $logger;
    }

    /**
     * save new records to purchased history table
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }

        try{
            $this->_purchasedHistoryResourceModel->insertMultiple($order);
        }catch (\Exception $e){
            $this->_logger->critical($e);
        }
    }
}
