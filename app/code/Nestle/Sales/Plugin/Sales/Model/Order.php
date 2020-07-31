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
namespace Nestle\Sales\Plugin\Sales\Model;

use Riki\Shipment\Model\ShipmentGridFactory;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\ShipmentItemRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;

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
     * Tracking if canceled order's status was changed by another function
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
        $canceledStatuses = [
            \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_CANCELED,
            \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_CVS_CANCELLATION_WITH_PAYMENT,
            \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_HOLD_CVS_NOPAYMENT
        ];
        $previousStatus = $result->getOrigData('status');
        $previousState = $result->getOrigData('state');
        if ($result->dataHasChangedFor('status') && in_array($previousStatus, $canceledStatuses)) {
            /** @var \Riki\Framework\Helper\Logger\Monolog $logger */
            $logger = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Riki\Framework\Helper\Logger\LoggerBuilder::class)
                ->setName('Nestle_Ned411')
                ->setFileName('ned411.log')
                ->pushHandlerByAlias(\Riki\Framework\Helper\Logger\LoggerBuilder::ALIAS_DATE_HANDLER)
                ->create();

            $logger->critical(new LocalizedException(__(
                'Order #%1 status has been changed from %2 to %3',
                $result->getId(),
                $result->getOrigData('status'),
                $result->getData('status')
            )));
        }

        if ($result->dataHasChangedFor('state') &&
            $previousState == \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_CANCELED
        ) {
            /** @var \Riki\Framework\Helper\Logger\Monolog $logger */
            $logger = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Riki\Framework\Helper\Logger\LoggerBuilder::class)
                ->setName('Nestle_Ned411')
                ->setFileName('ned411.log')
                ->pushHandlerByAlias(\Riki\Framework\Helper\Logger\LoggerBuilder::ALIAS_DATE_HANDLER)
                ->create();

            $logger->critical(new LocalizedException(__(
                'Order #%1 state has been changed from %2 to %3',
                $result->getId(),
                $result->getOrigData('state'),
                $result->getData('state')
            )));
        }

        return $result;
    }
}
