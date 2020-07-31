<?php
/**
 * Sales order plugin
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Shipment\Plugin
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Plugin\Sales\Model;
use Magento\Framework\Indexer\IndexerRegistry;
use Riki\Framework\Helper\Search;
use Riki\Shipment\Model\ShipmentGridFactory;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\ShipmentItemRepositoryInterface;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
/**
 * Class Order
 *
 * @category  RIKI
 * @package   Riki\Shipment\Plugin
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Order
{
    /**
     * @var ShipmentGridFactory
     */
    protected $shipmentGridFactory;

    protected $logger;

    protected $searchCriteria;

    protected $shipmentRepository;

    protected $orderItemRepository;

    protected $shipmentItemRepository;
    /**
     * Order constructor.
     * @param ShipmentGridFactory $shipmentGridFactory
     */
    public function __construct(
        ShipmentGridFactory $shipmentGridFactory,
        \Psr\Log\LoggerInterface $logger,
        ShipmentRepositoryInterface $shipmentRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        ShipmentItemRepositoryInterface $shipmentItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->shipmentGridFactory = $shipmentGridFactory;
        $this->logger = $logger;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchCriteria = $searchCriteriaBuilder;
        $this->orderItemRepository = $orderItemRepository;
        $this->shipmentItemRepository = $shipmentItemRepository;
    }
    /**
     * Sync order status in Shipment Grid when order status has been changed.
     *
     * @param \Magento\Sales\Model\Order $subject
     * @param \Magento\Sales\Model\Order $result
     *
     * @return mixed
     */
    public function afterAfterSave(
        \Magento\Sales\Model\Order $subject,
        \Magento\Sales\Model\Order $result
    ) {
        if ($result instanceof \Riki\Subscription\Model\Emulator\Order) {
            return $result;
        }
        if (!$result->getId()) {
            return $result;
        }
        if($result->dataHasChangedFor('status'))
        {
            $newOrderStatus = $result->getStatus();
            $criterial = $this->searchCriteria->addFilter('order_id', $result->getId())->create();
            $shipments = $this->shipmentRepository->getList($criterial);
            if($shipments->getSize())
            {
                foreach($shipments->getItems() as $shipmentObject)
                {
                    if($shipmentObject->getOrderStatus()!=$newOrderStatus) {
                        try {
                            $shipmentId = $shipmentObject->getId();
                            $shipmentObject->setOrderStatus($newOrderStatus)->save();
                            $shipmentGrid = $this->shipmentGridFactory->create()->load($shipmentId);
                            if ($shipmentGrid->getId()) {
                                $shipmentGrid->setOrderStatus($newOrderStatus);
                                $shipmentGrid->save();
                            }
                            $this->logger->info($shipmentObject->getIncrementId() . '---' . $shipmentObject->getShipmentStatus());
                        } catch (\Exception $e) {
                            $this->logger->info(__('Can not synchronize order status between order and shipments'));
                            $this->logger->critical($e);
                        }
                    }
                }
            }
        }
        return $result;
    }
    /**
     * Sync order status in Shipment Grid when order status has been changed.
     *
     * @param \Magento\Sales\Model\Order $subject
     * @param \Magento\Sales\Model\Order $result
     *
     * @return mixed
     */
    public function afterSave(
        \Magento\Sales\Model\Order $subject,
        \Magento\Sales\Model\Order $result
    ) {
        if ($result instanceof \Riki\Subscription\Model\Emulator\Order) {
            return $result;
        }
        if (!$result->getId()) {
            return $result;
        }
        $statusOrdercheck = [
            OrderStatus::STATUS_ORDER_COMPLETE,
            OrderStatus::STATUS_ORDER_SHIPPED_ALL,
            OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED
        ];


        if(in_array($result->getStatus(), $statusOrdercheck)){
            $statusShipmentCheck = [ShipmentStatus::SHIPMENT_STATUS_REJECTED,
                ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT,
                ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED
            ];
            $criterial = $this->searchCriteria->addFilter('order_id', $result->getId())->create();
            $shipments = $this->shipmentRepository->getList($criterial);
            if($shipments->getSize())
            {

                foreach($shipments->getItems() as $shipmentObject)
                {
                    if(in_array($shipmentObject->getShipmentStatus(), $statusShipmentCheck))
                    {
                        //update qty shipped
                        $shipmentItemCriterial = $this->searchCriteria->addFilter('parent_id', $shipmentObject->getEntityId())->create();
                        $shipmentItems = $this->shipmentItemRepository->getList($shipmentItemCriterial);
                        if($shipmentItems->getTotalCount())
                        {
                            foreach($shipmentItems as $shipItem)
                            {
                                $qtyShip = $shipItem->getQty();
                                $orderItem = $this->orderItemRepository->get($shipItem->getOrderItemId());
                                if($orderItem){
                                    $calculatedQty = $orderItem->getQtyShipped() + $qtyShip;
                                    if($calculatedQty > $orderItem->getQtyOrdered()){
                                        $calculatedQty = $orderItem->getQtyOrdered();
                                    }
                                    try{
                                        $orderItem->setQtyShipped($calculatedQty);
                                        $this->orderItemRepository->save($orderItem);
                                    }catch (\Exception $e){
                                        $this->logger->info(($e->getMessage()));
                                    }
                                }
                            }
                        }
                    }

                }
            }
        }
        return $result;
    }
}