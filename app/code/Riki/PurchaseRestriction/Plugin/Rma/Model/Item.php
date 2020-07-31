<?php
namespace Riki\PurchaseRestriction\Plugin\Rma\Model;

class Item
{
    protected $_orderItemRepository;

    protected $_resource;

    protected $_logger;

    /**
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $itemRepositoryInterface
     * @param \Riki\PurchaseRestriction\Model\ResourceModel\PurchaseHistory $purchaseHistory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Sales\Api\OrderItemRepositoryInterface $itemRepositoryInterface,
        \Riki\PurchaseRestriction\Model\ResourceModel\PurchaseHistory $purchaseHistory,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->_orderItemRepository = $itemRepositoryInterface;
        $this->_resource = $purchaseHistory;
        $this->_logger = $logger;
    }

    /**
     * update purchase restriction when rma was approved
     *
     * @param \Magento\Rma\Model\Item $subject
     * @param $result
     * @return mixed
     */
    public function afterAfterSave(
        \Magento\Rma\Model\Item $subject,
        $result
    ) {
        if (
            $subject->getOrigData('status') == \Magento\Rma\Model\Rma\Source\Status::STATE_APPROVED ||
            $subject->getStatus() == \Magento\Rma\Model\Rma\Source\Status::STATE_APPROVED
        ) {
            try{
                $item = $this->_orderItemRepository->get($subject->getOrderItemId());

                $qtyReturned = $item->getQtyReturned();

                if((float)$qtyReturned){
                    $this->_resource->deductQtyByOrderProduct($item->getOrderId(), $item->getSku(), $qtyReturned);
                }

            }catch (\Exception $e){
                $this->_logger->critical($e);
            }
        }

        return $result;
    }
}
