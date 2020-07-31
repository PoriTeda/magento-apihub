<?php
namespace Riki\Quote\Observer;

class ConvertOrderToQuote implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $_orderItemRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteria;

    public function __construct(
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ){
        $this->_orderItemRepository = $orderItemRepository;
        $this->_searchCriteria = $searchCriteriaBuilder;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getOrder();

        /*is reorder*/
        if( $order->getReordered() ){
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $observer->getQuote();

            foreach($quote->getAllItems() as &$item){
                /**
                 * @var \Magento\Quote\Model\Quote\Item $item
                 */
                $oldOrderItem = $this->getOldOrderItem( $order->getId(), $item->getProductId() );
                if( $oldOrderItem ){
                    $item->setUnitCase($oldOrderItem->getUnitCase());
                    $item->setUnitQty($oldOrderItem->getUnitQty());
                }
            }
        }

        return $this;
    }

    /**
     * @param $orderId
     * @param $productId
     * @return bool
     */
    public function getOldOrderItem( $orderId, $productId ){

        $criteria = $this->_searchCriteria->addFilter('order_id', $orderId )
            ->addFilter('product_id', $productId)
            ->create();

        $item = $this->_orderItemRepository->getList($criteria);

        if( $item->getTotalCount() ){
            return $item->getFirstItem();
        }
        return false;
    }
}