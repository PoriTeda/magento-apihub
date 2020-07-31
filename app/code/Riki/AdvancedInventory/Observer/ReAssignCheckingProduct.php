<?php
namespace Riki\AdvancedInventory\Observer;

use Magento\Framework\Event\Observer as EventObserver;
class ReAssignCheckingProduct implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Sales\Model\Order\ItemRepository
     */
    protected $itemFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Riki\Subscription\Logger\LoggerOrder
     */
    protected $loggerOrder;
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    protected $stockFactory;

    /**
     * Assignation constructor.
     * @param \Magento\Sales\Model\Order\ItemRepository $itemRepository
     */
    public function __construct
    (
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Riki\Subscription\Logger\LoggerOrder $loggerOrder,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Riki\AdvancedInventory\Model\StockFactory $stockFactory
    )
    {
        $this->itemFactory = $itemFactory;
        $this->messageManager = $messageManager;
        $this->loggerOrder = $loggerOrder;
        $this->quoteFactory = $quoteFactory;
        $this->registry = $registry;
        $this->productRepository = $productRepository;
        $this->stockFactory = $stockFactory;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer) {
        $assignationTo = $observer->getEvent()->getAssignationData();
        /* @var $order \Magento\Sales\Model\Order */
        $order = $observer->getEvent()->getOrder();
        if(is_null($order) || $order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }
        if ($order instanceof \Magento\Sales\Model\Order and $order->getData('re_assign_stock')) {
            $whAssignStatus = isset($assignationTo['wh_assign_status'])?$assignationTo['wh_assign_status']:null;
            if(is_array($whAssignStatus)) {
                foreach ($whAssignStatus as $orderItemId => $allStatus) {
                    foreach ($allStatus as $placeId =>$status) {
                        if($status == \Riki\AdvancedInventory\Model\Assignation::STOCK_STATUS_AVAILABLE_BACK_ORDER) {
                            $itemModel  = $this->itemFactory->create()->load($orderItemId);
                            if($itemModel->getId()) {
                                $productId = $itemModel->getProductId();
                                $stockCollection = $this->stockFactory->create()->getCollection()
                                                ->addFieldToFilter('product_id',$productId)
                                                ->addFieldToFilter('place_id',$placeId);
                                foreach ($stockCollection as $stock) {
                                    if ($stock->getData('backorder_allowed') == 1 and $stock->getData('backorder_delivery_date_allowed') == 0) {
                                        $message = __('Order has back-order product can\'t choose delivery date');
                                        throw new \Riki\AdvancedInventory\Exception\AssignationException($message);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}