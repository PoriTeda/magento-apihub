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
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\Shipment\Helper\ShipmentHistory;
use Riki\Shipment\Helper\Data;
use Riki\ShipmentImporter\Helper\Order as OrderHelperShipment;
use Magento\Backend\Model\Auth\Session as AuthorSession;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;

/**
 * Class Reject
 *
 * @category  RIKI
 * @package   Riki\Shipment\Controller\Adminhtml\Shipment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Reject extends \Magento\Backend\App\Action
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
    /**
     * @var Data
     */
    protected $shipmentHelper;
    /**
     * Reject constructor.
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
        Data $data
    )
    {
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
        $this->shipmentHelper = $data;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function execute()
    {
        $rejectStatus = ShipmentStatus::SHIPMENT_STATUS_REJECTED;
        $orderids = array();
        $backendUser = $this->authorSession->getUser();
        $adminer = $backendUser->getFirstName().' '.$backendUser->getLastName();
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

                        // If is payment NpAtobarai, keep the current payment status of this shipment,
                        // no need to update payment status when doing a reject action
                        if ($paymentMethod != NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE) {
                            $paymentStatus = $this->shipmentHelper->getPaymentStatus($paymentMethod);
                            $_ship->setData('payment_status', $paymentStatus);
                        }

                        $_ship->setData('shipment_status', $rejectStatus)
                            ->setData('shipment_date', $needDate)
                            ->save();
                        //add History
                        $historyData = [
                            'shipment_status' => $rejectStatus,
                            'shipment_id' => $_ship->getId()
                        ];
                        //add history to order
                        $orderObject->addStatusToHistory
                        (
                            $orderObject->getStatus(),
                            __('Rejected by ' . $adminer . ' - Shipment number: ' . $_ship->getIncrementId())
                        )
                            ->setIsCustomerNotified(false)->save();
                        $this->shipmentHistory->addShipmentHistory($historyData);

                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
            }

            $this->processOrderStatus($orderids);

            $this->messageManager->addSuccess(__('The shipments have been rejected.'));
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('sales/shipment/index/');
        }
    }

    /**
     * Validate status of an order
     *
     * @param $orderids
     * @throws \Exception
     */
    public function processOrderStatus($orderids)
    {
        $orderCollections = $this->orderCollection->create();
        $orderCollections->addFieldToFilter('entity_id', array('in' => $orderids));
        $uncheckStatus = [
            OrderStatus::STATUS_ORDER_COMPLETE
        ];
        if ($orderCollections->getSize()) {
            foreach ($orderCollections as $_order) {
                $paymentMethod = $_order->getPayment()->getMethod();
                $currentStatus = $this->orderHelper->getCurrentShipmentStatusOrder($_order);
                //update order
                if (
                    $currentStatus
                    && !in_array($_order->getStatus(), $uncheckStatus)
                ) {
                    switch ($currentStatus) {
                        case OrderHelperShipment::STEP_DELIVERY_COMPLETED:
                            /* REM-266 */
//                            if ($paymentMethod == "paygent") {//create invoice
//                                $this->dataImportHelper->createInvoiceOrder($_order);
//                            }elseif($paymentMethod == \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_COD){
//                                $_order->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
//                            }
                            $this->dataImportHelper->createInvoiceOrder($_order);
                            $_order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                            $_order->setStatus(OrderStatus::STATUS_ORDER_COMPLETE);
                            $_order->addStatusToHistory(OrderStatus::STATUS_ORDER_COMPLETE, __('Rejected shipments process'));
                            break;

                        case OrderHelperShipment::STEP_SHIPPED_ALL:

                            if($_order->getFreeOfCharge())
                                $_order->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);

                            $_order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                            $_order->setStatus(OrderStatus::STATUS_ORDER_SHIPPED_ALL);
                            $_order->addStatusToHistory(OrderStatus::STATUS_ORDER_SHIPPED_ALL, __('Rejected shipments process'));
                            break;
                        case OrderHelperShipment::STEP_PARTIALL_SHIPPED:

                            $_order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                            $_order->setStatus(OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED);
                            $_order->addStatusToHistory(OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED, __('Rejected shipments process'));
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
        if ($this->_authorization->isAllowed('Riki_Shipment::shipment_actions_rejected'))
        {
            return true;
        }
        return false;
    }


}