<?php
namespace Riki\Subscription\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
class Assignation implements ObserverInterface
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
     * Assignation constructor.
     * @param \Magento\Sales\Model\Order\ItemRepository $itemRepository
     */
    public function __construct
    (
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Riki\Subscription\Logger\LoggerOrder $loggerOrder,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\Registry $registry
    )
    {
        $this->itemFactory = $itemFactory;
        $this->messageManager = $messageManager;
        $this->loggerOrder = $loggerOrder;
        $this->quoteFactory = $quoteFactory;
        $this->registry = $registry;
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
        if ($order instanceof \Magento\Sales\Model\Order) {
            $quoteId = $order->getQuoteId();
            $quoteModel = $this->quoteFactory->create()->load($quoteId);
            if($quoteModel->getId() && $quoteModel->getData('riki_course_id') && $quoteModel->getData('riki_frequency_id')) {
                $whAssignStatus = isset($assignationTo['wh_assign_status'])?$assignationTo['wh_assign_status']:[];
                $this->checkItemAssignation($whAssignStatus);
                $oosProductIds = [];
                foreach ($whAssignStatus as $itemId => $assignData) {
                    $inStock = false;
                    foreach ($assignData as $posId => $status) {
                        if ($status == \Riki\AdvancedInventory\Model\Assignation::STOCK_STATUS_AVAILABLE) {
                            $inStock = true;
                            break;
                        }

                        if ($status == \Riki\AdvancedInventory\Model\Assignation::STOCK_STATUS_AVAILABLE_BACK_ORDER) {
                            $inStock = true;
                            break;
                        }

                        if ($status == \Riki\AdvancedInventory\Model\Assignation::STOCK_STATUS_AVAILABLE_PARTIAL) {
                            $inStock[] = $status;
                        }
                    }

                    if (!$inStock || (is_array($inStock) && count($inStock) != count($assignData))) {
                        $orderItem = $order->getItemsCollection()->getItemById($itemId);
                        if ($orderItem instanceof \Magento\Sales\Model\Order\Item) {
                            $oosProductIds[] = $orderItem->getProductId();
                        }
                    }
                }

                $this->registry->unregister('ai_assign_oos_product_ids');
                $this->registry->register('ai_assign_oos_product_ids', $oosProductIds);
            }
        }
    }
    public function checkItemAssignation($whAssignStatus) {
        $orderItemCannotAssign = [];
        $skuCannotAssign = [];
        if(is_array($whAssignStatus)) {
            foreach ($whAssignStatus as $orderItemId => $allStatus) {
                $i =0;
                foreach ($allStatus as $placeId =>$status) {
                    if($status != \Riki\ShipLeadTime\Plugin\AdvancedInventory\Model\Assignation::STOCK_STATUS_LEAD_TIME_INACTIVE) {
                        break;
                    }
                    $i++;
                }
                if($i == sizeof($allStatus)) {
                    $orderItemCannotAssign[] = $orderItemId;
                }
            }
        }
        if(sizeof($orderItemCannotAssign) > 0) {
            $salesOrderItemCollection = $this->itemFactory->create()->getCollection()
                ->addFieldToFilter('item_id',['in'=>$orderItemCannotAssign]);
            foreach ($salesOrderItemCollection as $item){
                /* @var $item \Magento\Sales\Model\Order\Item */
                $skuCannotAssign[] = $item->getSku();
            }
        }
        if(sizeof($skuCannotAssign) > 0) {
            if(!$this->registry->registry('skus_cannot_assign')){
                $this->registry->register('skus_cannot_assign',implode(', ',$skuCannotAssign));
            }
        }
    }

}