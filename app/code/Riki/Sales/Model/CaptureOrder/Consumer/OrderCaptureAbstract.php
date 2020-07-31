<?php
namespace Riki\Sales\Model\CaptureOrder\Consumer;

use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Riki\MessageQueue\Model\ResourceModel\QueueLock;
use Riki\Framework\Helper\Logger\LoggerBuilder;
use Riki\Sales\Model\Order;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\SubscriptionCourse\Model\Course\Type;
use Magento\Framework\MessageQueue\PublisherInterface;

class OrderCaptureAbstract
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @var LoggerBuilder
     */
    protected $loggerBuilder;

    /**
     * @var QueueLock
     */
    protected $queueLock;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $shipmentCollectionFactory;

    /**
     * @var \Riki\ShipmentImporter\Helper\Order
     */
    protected $shipmentStatusHelper;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * OrderCaptureAbstract constructor.
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param InvoiceService $invoiceService
     * @param PublisherInterface $publisher
     * @param QueueLock $queueLock
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory
     * @param \Riki\ShipmentImporter\Helper\Order $shipmentStatusHelper
     * @param LoggerBuilder $loggerBuilder
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        PublisherInterface $publisher,
        QueueLock $queueLock,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Riki\ShipmentImporter\Helper\Order $shipmentStatusHelper,
        \Riki\Framework\Helper\Logger\LoggerBuilder $loggerBuilder,
        TransactionFactory $transactionFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->publisher = $publisher;
        $this->loggerBuilder = $loggerBuilder;
        $this->queueLock = $queueLock;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->shipmentStatusHelper = $shipmentStatusHelper;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return Invoice
     * @throws LocalizedException
     */
    public function capture(
        Order $order
    ) {
        $invoice = $this->invoiceService->prepareInvoice($order);

        $invoice->setRequestedCaptureCase(
            /** capture offline if order become free order after applied delay point */
            $order->getGrandTotal()? Invoice::CAPTURE_ONLINE : Invoice::CAPTURE_OFFLINE
        );

        return $invoice->register();
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return $this
     * @throws \Exception
     */
    public function captureSuccessfullyCallback(
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Invoice $invoice
    ) {
        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $this->completeOrder($order);
        $transaction->addObject($order)
            ->addObject($invoice);

        $shipments = $this->getUpdatedShipments($order);

        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        foreach ($shipments as $shipment) {
            $shipment->setData('payment_status', PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED)
                ->setData('payment_date', new \Zend_Db_Expr('NOW()'))
                ->setData('collection_date', new \Zend_Db_Expr('NOW()'));

            $transaction->addObject($shipment);
        }

        $transaction->save();

        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    private function getOrderStatusAfterCapture(\Magento\Sales\Model\Order $order)
    {
        $shipmentStatus = $this->shipmentStatusHelper->getCurrentShipmentStatusOrder($order);
        if ($order->getRikiType() == Type::TYPE_ORDER_DELAY_PAYMENT
            && $shipmentStatus == \Riki\ShipmentImporter\Helper\Order::STEP_DELIVERY_COMPLETED) {
            return [
                OrderStatus::STATUS_ORDER_COMPLETE,
                Order::STATE_COMPLETE
            ];
        }
        return [
            OrderStatus::STATUS_ORDER_SHIPPED_ALL,
            Order::STATE_PROCESSING
        ];
    }

    /**
     * Complete order after capture success
     *
     * @param $order
     * @return $this
     */
    public function completeOrder(\Magento\Sales\Model\Order $order)
    {
        list($status, $state) = $this->getOrderStatusAfterCapture($order);

        $order->setState($state)
            ->setStatus($status)
            ->setPaymentStatus(
                PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED
            );

        if ($order->getGrandTotal()) {
            $order->addStatusToHistory(
                $status,
                'Payment has been captured success.'
            )->setIsCustomerNotified(false);
        } else {
            $order->addStatusToHistory(
                $status,
                ''
            )->setIsCustomerNotified(false);
        }

        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Sales\Api\Data\ShipmentInterface[]
     */
    protected function getUpdatedShipments(\Magento\Sales\Model\Order $order)
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipmentCollection */
        $shipmentCollection = $this->shipmentCollectionFactory->create();
        $shipmentCollection->setOrderFilter($order)
            ->addFieldToFilter('ship_zsim', ['neq' => 1]);
        return $shipmentCollection->getItems();
    }

    /**
     * @param $name
     * @return \Riki\Framework\Helper\Logger\Monolog
     * @throws \Exception
     */
    protected function createLogger($name)
    {
        return $this->loggerBuilder
            ->setName('Riki_Paygent')
            ->setFileName($name . '.log')
            ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
            ->create();
    }
}
