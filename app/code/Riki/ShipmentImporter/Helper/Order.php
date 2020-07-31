<?php
/**
 * Riki Shipment Importer
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ShipmentImporter\Helper;
use Magento\Bundle\Block\Adminhtml\Catalog\Product\Edit\Tab\Bundle\Option\Search;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\Sales\Helper\ConnectionHelper;
/**
 * Class Order Helper
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Order extends AbstractHelper {

    CONST STEP_PARTIALL_SHIPPED = 'partially_shipped';
    CONST STEP_SHIPPED_ALL = 'shipped_all';
    CONST STEP_DELIVERY_COMPLETED = 'delivery_completed';
    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchBuilder;

    protected $connectionHelper;

    /**
     * Order constructor.
     * @param Context $context
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    Public function __construct(
        Context $context,
        ShipmentRepositoryInterface $shipmentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ConnectionHelper $connectionHelper
    ) {
        parent::__construct($context);
        $this->shipmentRepository = $shipmentRepository;
        $this->searchBuilder = $searchCriteriaBuilder;
        $this->connectionHelper = $connectionHelper;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function getCurrentShipmentStatusOrder
    (
        \Magento\Sales\Model\Order $order
    )
    {
        $criteria = $this->searchBuilder
            ->addFilter('order_id', $order->getId())
            ->addFilter('ship_zsim',1, 'neq')
            ->create();
        $orderStatus = false;
        $shipmentCollection = $this->shipmentRepository->getList($criteria);
        $shipCreated = 0;
        $shipExported = 0;
        $shipShippedOut = 0;
        $shipRejected = 0;
        $shipDeliveryCompleted = 0;
        $totalAvailableShipment = 0;
        if($shipmentCollection->getTotalCount())
        {
            foreach($shipmentCollection->getItems() as $_ship)
            {
                if(!$_ship->getData('ship_zsim'))
                {
                    $shipStatus = $_ship->getData('shipment_status');
                    $totalAvailableShipment++;
                    switch($shipStatus)
                    {
                        case ShipmentStatus::SHIPMENT_STATUS_CREATED:
                            $shipCreated++;
                            break;
                        case ShipmentStatus::SHIPMENT_STATUS_EXPORTED:
                            $shipExported++;
                            break;
                        case ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT:
                            $shipShippedOut++;
                            break;
                        case ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED:
                            $shipDeliveryCompleted++;
                            break;
                        case ShipmentStatus::SHIPMENT_STATUS_REJECTED:
                            $shipRejected++;
                            break;
                    }
                }
            }
            $shipGroup1 = ($shipDeliveryCompleted || $shipShippedOut || $shipRejected);
            $shipGroup2 = ($shipCreated || $shipExported);
            $shipGroup3 = ($shipShippedOut || $shipDeliveryCompleted || $shipRejected);
            $shipGroup4 = ($shipDeliveryCompleted || $shipRejected);

            $total1  = $shipShippedOut + $shipDeliveryCompleted + $shipRejected;
            $total2 = $shipDeliveryCompleted + $shipRejected;
            //case delivery completed
            if($shipGroup4 && $total2 == $totalAvailableShipment && !$shipShippedOut)
            {
                $orderStatus = self::STEP_DELIVERY_COMPLETED;

            }
            elseif ($shipGroup3 && $total1 == $totalAvailableShipment)
            {
                $orderStatus = self::STEP_SHIPPED_ALL;

            }
            elseif($shipGroup1 && $shipGroup2 && $totalAvailableShipment)
            {
                $orderStatus = self::STEP_PARTIALL_SHIPPED;
            }
            else
            {
                $orderStatus = false;
            }
        }
        return $orderStatus;
    }//end function

    /**
     * @param $orderId
     * @return array|bool
     */
    public function getShipmentsArray($orderId)
    {
        $criteria = $this->searchBuilder
            ->addFilter('order_id', $orderId)
            ->addFilter('ship_zsim',1, 'neq')
            ->create();
        $shipmentCollection = $this->shipmentRepository->getList($criteria);
        if($shipmentCollection->getTotalCount())
        {
            $shipArray = array();
            foreach($shipmentCollection->getItems() as $_ship )
            {
                $shipArray[$_ship->getIncrementId()] = $_ship->getShipmentStatus();
            }
            return $shipArray;
        }
        else
        {
            return false;
        }
    }
    /**
     * @param array $shipments
     * @return bool|string
     */
    public function getCurrentOrderStatusBaseOnShipments(array $shipments)
    {
        $shipCreated = 0;
        $shipExported = 0;
        $shipShippedOut = 0;
        $shipRejected = 0;
        $shipDeliveryCompleted = 0;
        $totalAvailableShipment = 0;
        $orderStatus = false;
        if (!empty($shipments)) {
            foreach ($shipments as $shipKey => $shipStatus) {
                $totalAvailableShipment++;
                switch ($shipStatus) {
                    case ShipmentStatus::SHIPMENT_STATUS_CREATED:
                        $shipCreated++;
                        break;
                    case ShipmentStatus::SHIPMENT_STATUS_EXPORTED:
                        $shipExported++;
                        break;
                    case ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT:
                        $shipShippedOut++;
                        break;
                    case ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED:
                        $shipDeliveryCompleted++;
                        break;
                    case ShipmentStatus::SHIPMENT_STATUS_REJECTED:
                        $shipRejected++;
                        break;
                }
            }
            $shipGroup1 = ($shipDeliveryCompleted || $shipShippedOut || $shipRejected);
            $shipGroup2 = ($shipCreated || $shipExported);
            $allDeliveryComplete = ($shipDeliveryCompleted && $shipDeliveryCompleted == $totalAvailableShipment);
            $allShippedOut = ($shipShippedOut && $shipShippedOut == $totalAvailableShipment);
            $allRejected = ($shipRejected && $shipRejected == $totalAvailableShipment);
            $allRejectedAndDeliveryComplete = (($shipRejected || $shipDeliveryCompleted)
                && ($shipDeliveryCompleted + $shipRejected == $totalAvailableShipment));
            $allRejectedAndShippedOut = (($shipShippedOut || $shipRejected)
                && ($shipShippedOut + $shipRejected == $totalAvailableShipment));
            $allRejectedAndShippedOutAndDeliveryComplete = $shipShippedOut && $shipRejected && $shipDeliveryCompleted
                && ($shipShippedOut + $shipRejected + $shipDeliveryCompleted == $totalAvailableShipment);
            //case delivery completed
            if (($allDeliveryComplete || $allRejected || $allRejectedAndDeliveryComplete) && !$shipShippedOut) {
                $orderStatus = self::STEP_DELIVERY_COMPLETED;
            } elseif ($allShippedOut || $allRejectedAndShippedOut || $allRejectedAndShippedOutAndDeliveryComplete) {
                $orderStatus = self::STEP_SHIPPED_ALL;
            } elseif ($shipGroup1 && $shipGroup2 && $totalAvailableShipment) {
                $orderStatus = self::STEP_PARTIALL_SHIPPED;
            }
        }
        return $orderStatus;
    }//end function

    /**
     * @param string $orderId
     * @param array $data
     * @return void
     * @throws \Exception
     */
    public function massUpdateShipments($orderId, $data = [])
    {
        $criteria = $this->searchBuilder
            ->addFilter('order_id', $orderId)
            ->addFilter('ship_zsim',1, 'neq')
            ->create();
        $shipmentCollection = $this->shipmentRepository->getList($criteria);
        $shipmentIds = $shipmentCollection->getAllIds();
        if (!empty($shipmentIds)) {
            $connection = $this->connectionHelper->getSalesConnection();
            $where = ['entity_id IN (?)' => $shipmentIds];
            $bind = [
                'payment_status' => $data['payment_status'],
                'payment_date' => $data['payment_date'],
                'collection_date' => $data['payment_date']
            ];
            try {
                $connection->beginTransaction();
                $connection->update($connection->getTableName('sales_shipment'), $bind, $where);
                $salesShipmentGridTbl = $connection->getTableName('sales_shipment_grid');
                if (!$connection->tableColumnExists($salesShipmentGridTbl, 'collection_date')) {
                    unset($bind['collection_date']);
                }
                $connection->update($salesShipmentGridTbl, $bind, $where);
                $connection->commit();
            } catch(\Exception $e) {
                $connection->rollBack();
                throw $e;
            }
        }
    }
}
