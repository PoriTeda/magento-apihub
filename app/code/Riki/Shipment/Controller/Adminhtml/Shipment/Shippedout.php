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
use Riki\ShipmentImporter\Helper\Order as OrderHelperShipment;
use Riki\ShipmentImporter\Helper\Data as ImporterHelperShipment;
use Magento\Backend\Model\Auth\Session as AuthorSession;
/**
 * Class Shipped Out
 *
 * @category  RIKI
 * @package   Riki\Shipment\Controller\Adminhtml\Shipment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Shippedout extends \Magento\Backend\App\Action
{
    const FREE_PAYMENT = 'free';
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
     * @var \Riki\Shipment\Helper\Data
     */
    protected $helper;

    /**
     * @var \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface
     */
    protected $shipmentSapExportedRepository;

    /**
     * Shipped out constructor.
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
     * @param \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface $shipmentSapExportedRepository
     */
    public function __construct(
        Action\Context $context,
        CollectionFactory $collectionFactory,
        DateTime $dateTime,
        TimezoneInterface $timezone,
        Filter $filter,
        OrderCollectionFactory $orderCollectionFactory,
        ImporterHelperShipment $dataHelper,
        OrderHelperShipment $orderHelper,
        ShipmentHistory $shipmentHistory,
        \Psr\Log\LoggerInterface $logger,
        AuthorSession $session,
        \Riki\Shipment\Helper\Data $helper,
        \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface $shipmentSapExportedRepository
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
        $this->shipmentSapExportedRepository = $shipmentSapExportedRepository;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function execute()
    {
        $backendUser = $this->authorSession->getUser();
        $adminer = $backendUser->getFirstName().' '.$backendUser->getLastName();
        $needDate = $this->dateTime->gmtDate('Y-m-d');
        $shipments = $this->filter->getCollection($this->shipmentCollection->create());
        $totalShipments = $shipments->getSize();
        $limitShipments = \Riki\Shipment\Helper\Data::MAX_SHIPMENTS_PROCEED;
        $shipmentStatus = ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT;
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
                        $paymentStatus = $this->getPaymentStatus($paymentMethod, $orderObject);

                        if ($paymentStatus) {
                            $_ship->setPaymentStatus($paymentStatus);
                            $_ship->setCollectionDate($needDate);
                            $_ship->setPaymentDate($needDate);
                        }

                        $_ship->setData('shipment_status', $shipmentStatus)
                            ->setData('shipment_date', $needDate)
                            ->setData('shipped_out_date', $needDate);
                        /**
                         * set flag status shipment to Reconciliation after change status to shipped_out
                         * 1 is prepare;2 finish
                         */
                        $_ship->setData('is_reconciliation_exported', 1);

                        /*set flag is_exported_sap to 1(waiting for exported to SAP)*/
                        $_ship->setData(
                            'is_exported_sap', \Riki\SapIntegration\Model\Api\Shipment::WAITING_FOR_EXPORT
                        );

                        if ($paymentMethod == \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE) {
                            /*set flag_export_invoice_sales_shipment = 0 (waiting for export to bi - invoice shipment) */
                            $_ship->setData('flag_export_invoice_sales_shipment', 0);
                        }

                        $_ship->save();

                        /*sync data for sap exported table after shipment was shipped out*/
                        $this->syncDataForShipmentSapExported($_ship);

                        //add History
                        $historyData = [
                            'shipment_status' => $shipmentStatus,
                            'shipment_id' => $_ship->getId()
                        ];
                        //add history to order
                        $orderObject->addStatusToHistory
                        (
                            $orderObject->getStatus(),
                            __('Shipped out by ' . $adminer . ' - Shipment number: ' . $_ship->getIncrementId())
                        )
                            ->setIsCustomerNotified(false)->save();

                        $this->shipmentHistory->addShipmentHistory($historyData);

                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
            }

            $this->processOrderStatus($orderids);

            $this->messageManager->addSuccess(__('The shipments have been shipped out.'));
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
            OrderStatus::STATUS_ORDER_COMPLETE,
            OrderStatus::STATUS_ORDER_SHIPPED_ALL
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
                            if ($paymentMethod == "paygent") {//create invoice
                                $this->dataImportHelper->createInvoiceOrder($_order);
                            }
                            $_order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                            $_order->setStatus(OrderStatus::STATUS_ORDER_COMPLETE);
                            $_order->addStatusToHistory(OrderStatus::STATUS_ORDER_COMPLETE, __('Shipped out shipments process'));
                            break;

                        case OrderHelperShipment::STEP_SHIPPED_ALL:

                            if($_order->getFreeOfCharge() || $paymentMethod =='free')
                            {
                                $_order->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
                            }
                            $_order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                            $_order->setStatus(OrderStatus::STATUS_ORDER_SHIPPED_ALL);
                            $_order->addStatusToHistory(OrderStatus::STATUS_ORDER_SHIPPED_ALL, __('Shipped out shipments process'));
                            break;
                        case OrderHelperShipment::STEP_PARTIALL_SHIPPED:

                            $_order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                            $_order->setStatus(OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED);
                            $_order->addStatusToHistory(OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED, __('Shipped out shipments process'));
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
        if ($this->_authorization->isAllowed('Riki_Shipment::shipment_actions_shippedout'))
        {
            return true;
        }
        return false;
    }

    /**
     * Payment status for shipment which has been shipped out.
     *
     * @param $paymentMethod
     * @param $order
     * @return bool|string
     */
    public function getPaymentStatus($paymentMethod, $order)
    {
        $paymentStatus = false;

        switch ($paymentMethod) {
            case \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE:
                $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                break;
            case self::FREE_PAYMENT:
                if ($order && $order->getChargeType() == \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_NORMAL) {
                    $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                }
                break;
        }
        return $paymentStatus;
    }

    /**
     * Change SAP flag after shipment was shipped out
     *
     * @param $shipment
     */
    protected function syncDataForShipmentSapExported(
        \Magento\Sales\Model\Order\Shipment $shipment
    ) {
        try {
            $shipmentSapExported = $this->shipmentSapExportedRepository->getById($shipment->getId());
        } catch (\Exception $e) {
            $this->logger->info('Cannot get SAP data for shipment #'.$shipment->getIncrementId());
            return;
        }

        $shipmentSapExported->setIsExportedSap($shipment->getIsExportedSap());

        try {
            $this->shipmentSapExportedRepository->save($shipmentSapExported);
            $this->logger->info(
                'SAP flag of shipment #'.$shipment->getIncrementId().
                ' has been changed to '.$shipmentSapExported->getIsExportedSap()
            );
        } catch (\Exception $e) {
            $this->logger->info(
                'Cannot change SAP flag to '.$shipment->getIsExportedSap().' for shipment #'.$shipment->getIncrementId()
            );
            return;
        }
    }
}