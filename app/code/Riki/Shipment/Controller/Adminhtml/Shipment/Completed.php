<?php
/**
 * Shipment Reject Action
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Shipment\Controller\Adminhtml\Shipment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Shipment\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\Shipment\Helper\ShipmentHistory;
use Riki\ShipmentImporter\Helper\Order as OrderHelperShipment;
use Magento\Backend\Model\Auth\Session as AuthorSession;

/**
 * Class completed
 *
 * @category  RIKI
 * @package   Riki\Shipment\Controller\Adminhtml\Shipment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Completed extends \Magento\Backend\App\Action
{
    /**
     * @var CollectionFactory
     */
    protected $shipmentCollection;
    /**
     * @var TimezoneInterface
     */
    protected $timeZone;
    /**
     * @var DateTime
     */
    protected $dateTime;
    /**
     * @var Filter
     */
    protected $filter;
    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollection;
    /**
     * @var \Riki\ShipmentImporter\Helper\Data
     */
    protected $dataImportHelper;
    /**
     * @var ShipmentHistory
     */
    protected $shipmentHistory;
    /**
     * @var \Riki\ShipmentImporter\Helper\Order
     */
    protected $orderHelper;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var AuthorSession
     */
    protected $authorSession;

    protected $helper;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var \Riki\SubscriptionProfileDisengagement\Helper\Data
     */
    private $disengageProfileHelper;

    /**
     * @var \Riki\Sales\Helper\Order
     */
    private $salesOrderHelper;

    /**
     * Completed constructor.
     *
     * @param Action\Context $context
     * @param CollectionFactory $collectionFactory
     * @param DateTime $dateTime
     * @param TimezoneInterface $timezone
     * @param Filter $filter
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param \Riki\ShipmentImporter\Helper\Data $dataHelper
     * @param OrderHelperShipment $orderHelper
     * @param ShipmentHistory $shipmentHistory
     * @param \Psr\Log\LoggerInterface $logger
     * @param AuthorSession $session
     * @param \Riki\Shipment\Helper\Data $helper
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Riki\SubscriptionProfileDisengagement\Helper\Data $disengageProfileHelper
     * @param \Riki\Sales\Helper\Order $salesOrderHelper
     */
    public function __construct(
        Action\Context $context,
        CollectionFactory $collectionFactory,
        DateTime $dateTime,
        TimezoneInterface $timezone,
        Filter $filter,
        OrderCollectionFactory $orderCollectionFactory,
        \Riki\ShipmentImporter\Helper\Data $dataHelper,
        OrderHelperShipment $orderHelper,
        ShipmentHistory $shipmentHistory,
        \Psr\Log\LoggerInterface $logger,
        AuthorSession $session,
        \Riki\Shipment\Helper\Data $helper,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Riki\SubscriptionProfileDisengagement\Helper\Data $disengageProfileHelper,
        \Riki\Sales\Helper\Order $salesOrderHelper
    ) {
        parent::__construct($context);
        $this->dateTime = $dateTime;
        $this->timeZone = $timezone;
        $this->shipmentCollection = $collectionFactory;
        $this->filter = $filter;
        $this->orderCollection = $orderCollectionFactory;
        $this->dataImportHelper = $dataHelper;
        $this->shipmentHistory = $shipmentHistory;
        $this->orderHelper = $orderHelper;
        $this->logger = $logger;
        $this->authorSession = $session;
        $this->helper = $helper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->disengageProfileHelper = $disengageProfileHelper;
        $this->salesOrderHelper = $salesOrderHelper;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function execute()
    {
        $shipmentStatus = ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED;
        $backendUser = $this->authorSession->getUser();
        $adminer = $backendUser->getFirstName().' '.$backendUser->getLastName();
        $orderids = array();
        $needDate = $this->dateTime->gmtDate('Y-m-d');
        $shipments = $this->filter->getCollection($this->shipmentCollection->create());
        $totalShipments = $shipments->getSize();
        $limitShipments = \Riki\Shipment\Helper\Data::MAX_SHIPMENTS_PROCEED;
        if($shipments->getSize() > $limitShipments )
        {
            $message = __('Total %1 shipments found. We can not proceed over than %2 shipments',$totalShipments, $limitShipments);
            $this->messageManager->addError($message);
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('sales/shipment/index/');
        }
        else
        {
            if ($shipments->getSize()) {
                foreach ($shipments as $_ship) {
                    try {
                        $orderids[$_ship->getOrderId()] = $_ship->getOrderId();
                        $orderObject = $_ship->getOrder();
                        $paymentMethod = $orderObject->getPayment()->getMethod();
                        $paymentStatus = $this->getPaymentStatus($paymentMethod);
                        if ($paymentStatus) {
                            $_ship->setData('payment_status', $paymentStatus)
                                ->setData('payment_date', $needDate);
                        }
                        $_ship->setData('shipment_status', $shipmentStatus)
                            ->setData('shipment_date', $needDate)
                            ->setData('delivery_complete_date', $needDate)
                            ->save();
                        //add History
                        $historyData = [
                            'shipment_status' => $shipmentStatus,
                            'shipment_id' => $_ship->getId()
                        ];
                        //add history to order
                        $orderObject->addStatusToHistory
                        (
                            $orderObject->getStatus(),
                            __('Completion of delivery by ' . $adminer . ' - Shipment number: ' . $_ship->getIncrementId())
                        )
                            ->setIsCustomerNotified(false)->save();

                        $this->shipmentHistory->addShipmentHistory($historyData);

                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
            }

            $this->processOrderStatus($orderids);

            $this->messageManager->addSuccess(__('The shipments have been completed.'));
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('sales/shipment/index/');
        }
    }

    /**
     * Validate status of an order
     *
     * @param string $orderids
     * @throws \Exception
     */
    public function processOrderStatus($orderids)
    {
        $orderCollections = $this->orderCollection->create();
        $orderCollections->addFieldToFilter('entity_id', ['in' => $orderids]);
        $uncheckStatus = [
            OrderStatus::STATUS_ORDER_COMPLETE];
        if ($orderCollections->getSize()) {
            foreach ($orderCollections as $_order) {
                $paymentMethod = $_order->getPayment()->getMethod();
                $currentStatus = $this->orderHelper->getCurrentShipmentStatusOrder($_order);
                $captureSuccess = false;
                //update order
                if (
                    $currentStatus
                    && !in_array($_order->getStatus(), $uncheckStatus)
                ) {

                    switch ($currentStatus) {
                        case OrderHelperShipment::STEP_DELIVERY_COMPLETED:
                            $orderId = $_order->getId();
                            $createInvoice = true;
                            if ($paymentMethod == Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
                                $createInvoice = $this->canCreateInvoiceForCodOrder($orderId);
                            }

                            if ($this->salesOrderHelper->isDelayPaymentOrder($_order)
                                && !$this->disengageProfileHelper
                                    ->isDisengageMode($_order->getData('subscription_profile_id'))) {
                                $createInvoice = false;
                            }
                            if ($createInvoice) {
                                $captureSuccess = $this->dataImportHelper->createInvoiceOrder($_order);
                            }
                            if ($captureSuccess) {
                                $_order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                                $_order->setStatus(OrderStatus::STATUS_ORDER_COMPLETE);
                                $_order->addStatusToHistory(
                                    OrderStatus::STATUS_ORDER_COMPLETE,
                                    __('Completion of delivery shipments process')
                                );
                            }
                            break;

                        case OrderHelperShipment::STEP_SHIPPED_ALL:
                            if ($_order->getFreeOfCharge()) {
                                $_order->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
                            }
                            $_order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                            $_order->setStatus(OrderStatus::STATUS_ORDER_SHIPPED_ALL);
                            $_order->addStatusToHistory(
                                OrderStatus::STATUS_ORDER_SHIPPED_ALL,
                                __('Completion of delivery shipments process')
                            );
                            break;
                        case OrderHelperShipment::STEP_PARTIALL_SHIPPED:
                            $_order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                            $_order->setStatus(OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED);
                            $_order->addStatusToHistory(
                                OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED,
                                __('Completion of delivery shipments process')
                            );
                            break;
                    }
                    try {
                        $_order->save();
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
            }
        }//endif
    }//end function
    /**
     * Is Allow.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        if ($this->_authorization->isAllowed('Riki_Shipment::shipment_actions_completed'))
        {
            return true;
        }
        return false;
    }

    /**
     * @param $paymentMethod
     * @return string
     */
    public function getPaymentStatus($paymentMethod)
    {
        $paymentStatus = '';
        switch($paymentMethod)
        {
            case \Bluecom\Paygent\Model\Paygent::CODE:
                $paymentStatus = '';
                break;
            case Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE:
                $paymentStatus = '';
                break;
            case \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE:
                $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                break;
            case \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE;
                $paymentStatus ='';
                break;
            case NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE:
                $paymentStatus = '';
                break;
            default:
                $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                break;
        }
        return $paymentStatus;
    }

    /**
     * @param string $orderId
     *
     * @return bool
     */
    public function canCreateInvoiceForCodOrder($orderId)
    {
        /*list of payment status which can create invoice*/
        $allowedPaymentStatus = [
            PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
            PaymentStatus::SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE
        ];
        $allowedShipmentStatus = [
            ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
            ShipmentStatus::SHIPMENT_STATUS_REJECTED
        ];

        $shipmentCollection = $this->shipmentCollection->create();

        $shipmentCollection->addFieldToFilter('order_id', ['eq' => $orderId]);
        $shipmentCollection->addFieldToFilter(
            ['shipment_status', 'payment_status'],
            [
                ['neq' => ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED],
                ['neq' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED]
            ]
        );
        $shipmentCollection->addFieldToFilter('shipment_status', ['neq' => ShipmentStatus::SHIPMENT_STATUS_REJECTED]);
        $shipmentCollection->addFieldToFilter(
            ['shipment_status', 'grand_total'],
            [
                ['neq' => ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED],
                ['eq' => 0]
            ]
        );
        $shipmentCollection->addFieldToFilter('ship_zsim', ['neq' => 1]);

        $rs = true;
        if ($shipmentCollection->getSize() > 0) {
            $rs = false;
        }
        return $rs;
    }
}
