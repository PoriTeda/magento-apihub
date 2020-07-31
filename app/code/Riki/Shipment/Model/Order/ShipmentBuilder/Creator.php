<?php
// @codingStandardsIgnoreFile
/**
 * Shipment Creator
 *
 * PHP version 7
 *
 * @category  RIKI Shipment
 * @package   Riki\Shipment\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Model\Order\ShipmentBuilder;
use Magento\Framework\App\ObjectManager;
use Riki\AutomaticallyShipment\Model\CreateShipment;
use Magento\Sales\Api\OrderRepositoryInterface;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
/**
 * Class Creator
 *
 * @category  RIKI Shipment
 * @package   Riki\Shipment\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Creator
{
    const SHIPMENT_CREATOR_NEW = 0;

    const SHIPMENT_CREATOR_WAITING_HANDLED = 1;

    const SHIPMENT_CREATOR_HANDLED = 2;
    /**
     * @var \Riki\Shipment\Logger\LoggerPublishMQ
     */
    protected $logger;
    /**
     * @var CreateShipment
     */
    protected $shipmentCreator;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;
    /**
     * @var \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface
     */
    protected $_orderStatusHistory;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriticalBuilder;

    /**
     * Creator constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\App\State $state
     * @param CreateShipment $createShipment
     * @param OrderRepositoryInterface $orderFactory
     * @param \Riki\ShipmentExporter\Logger\LoggerShipCreator $logger
     * @param \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct
    (
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\App\State $state,
        CreateShipment $createShipment,
        OrderRepositoryInterface $orderFactory,
        \Riki\ShipmentExporter\Logger\LoggerShipCreator $logger,
        \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->shipmentCreator = $createShipment;
        $this->orderRepository = $orderRepository;
        $this->appState = $state;
        $this->_orderFactory = $orderFactory;
        $this->logger = $logger;
        $this->_orderStatusHistory = $orderStatusHistoryRepository;
        $this->_searchCriticalBuilder = $searchCriteriaBuilder;
    }

    /**
     * Create shipment by Message queue
     *
     * @param \Riki\Shipment\Api\ShipmentBuilder\ProfileBuilderInterface $message
     * @throws \Exception
     */
    public function createShipmentFromQueue (
        \Riki\Shipment\Api\ShipmentBuilder\ProfileBuilderInterface $message
    ) {
        $orderId = 0;
        foreach ($message->getItems() as $profileObject) {
            $orderId = $profileObject->getOrderId();
        }
        /** @var \Magento\Sales\Model\Order $order $order */
        $order = $this->getOrderById($orderId);
        if ($order) {
            $orderNumber = $order->getIncrementId();
            ObjectManager::getInstance()->create("Nestle\Debugging\Helper\DebuggingHelper")
                ->logClass($this)
                ->log('Start creating shipments for order #: '.$orderNumber)
                ->logServerIp()
                ->logBacktrace()
                ->save("MGU-509");
            if ($order->canShip()) {
                $this->logger->info('Start creating shipments for order #: '.$orderNumber);
                /*create shipment for this order*/
                try{
                    $createShipment = $this->shipmentCreator->createShipment($order, __('Shipment Create Cron'));
                }catch (\Exception $exception){
                    ObjectManager::getInstance()->create("Nestle\Debugging\Helper\DebuggingHelper")
                        ->logClass($this)
                        ->addMessage('error create shipment for order #: ' . $orderNumber)
                        ->addMessage($exception->getMessage())
                        ->logServerIp()
                        ->logBacktrace()
                        ->save("MGU-509");
                }

                /*only change order status after create shipment success*/
                if ($createShipment) {
                    $this->logger->info('Create shipment for order '.$orderNumber.' successfully');
                } else {
                    $this->logger->info('Cannot creating shipment for order '.$orderNumber);

                    // Update published_message = 0 again for this order
                    // When cannot creating shipment due to error from system [Ex: Lock wait timeout exceeded]
                    if ($order->getData('published_message') == self::SHIPMENT_CREATOR_WAITING_HANDLED) {
                        $order->setData(
                            'published_message',
                            self::SHIPMENT_CREATOR_NEW
                        );
                        $order->getResource()->saveAttribute($order, 'published_message');
                    }
                }
            } else {
                $this->logger->info(__('Order #: %1 can not ship',$orderNumber));
            }
        }
    }

    /**
     * Get order by entity_id
     *
     * @param $orderId
     * @return bool|\Magento\Sales\Api\Data\OrderInterface
     */
    private function getOrderById($orderId)
    {
        try {
            return $this->orderRepository->get($orderId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->info('Order does not exist #:'.$orderId);
            $this->logger->critical($e);
            return false;
        }
    }
}
