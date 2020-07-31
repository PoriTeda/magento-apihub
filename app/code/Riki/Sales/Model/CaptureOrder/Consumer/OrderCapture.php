<?php
namespace Riki\Sales\Model\CaptureOrder\Consumer;

use Bluecom\Paygent\Exception\PaygentCaptureException;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Riki\MessageQueue\Api\FailureItemInterface;
use Riki\MessageQueue\Api\FailureItemInterfaceFactory;
use Riki\MessageQueue\Exception\MessageLocalizedException;
use Riki\MessageQueue\Model\Consumer\Failure;
use Riki\MessageQueue\Model\ResourceModel\QueueLock;
use Riki\Sales\Api\CaptureItemInterface;
use Riki\Sales\Api\CaptureItemInterfaceFactory;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\Subscription\Exception\DelayPaymentReAuthorizeException;
use Riki\Subscription\Exception\DelayPaymentSaveReAuthorizeDataException;
use Riki\Subscription\Model\DelayPaymentOrder;
use Riki\Subscription\Model\DelayPaymentOrderFactory;
use Magento\Framework\MessageQueue\PublisherInterface;

class OrderCapture extends OrderCaptureAbstract
{
    const CAPTURE_ORDER_QUEUE_NAME = 'sales.order.capture';

    /**
     * @var FailureItemInterfaceFactory
     */
    protected $failureItemInterfaceFactory;

    /**
     * @var CaptureItemInterfaceFactory
     */
    protected $captureItemInterfaceFactory;

    /**
     * @var DelayPaymentOrderFactory
     */
    protected $delayPaymentOrderFactory;

    /**
     * @var
     */
    protected $logger;

    /**
     * OrderCapture constructor.
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param InvoiceService $invoiceService
     * @param PublisherInterface $publisher
     * @param QueueLock $queueLock
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory
     * @param \Riki\ShipmentImporter\Helper\Order $shipmentStatusHelper
     * @param \Riki\Framework\Helper\Logger\LoggerBuilder $loggerBuilder
     * @param TransactionFactory $transactionFactory
     * @param FailureItemInterfaceFactory $failureItemInterfaceFactory
     * @param CaptureItemInterfaceFactory $captureItemInterfaceFactory
     * @param DelayPaymentOrderFactory $delayPaymentOrderFactory
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        PublisherInterface $publisher,
        QueueLock $queueLock,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Riki\ShipmentImporter\Helper\Order $shipmentStatusHelper,
        \Riki\Framework\Helper\Logger\LoggerBuilder $loggerBuilder,
        TransactionFactory $transactionFactory,
        FailureItemInterfaceFactory $failureItemInterfaceFactory,
        CaptureItemInterfaceFactory $captureItemInterfaceFactory,
        DelayPaymentOrderFactory $delayPaymentOrderFactory
    ) {
        $this->failureItemInterfaceFactory = $failureItemInterfaceFactory;
        $this->delayPaymentOrderFactory = $delayPaymentOrderFactory;
        $this->captureItemInterfaceFactory = $captureItemInterfaceFactory;

        parent::__construct(
            $orderRepository,
            $invoiceService,
            $publisher,
            $queueLock,
            $shipmentCollectionFactory,
            $shipmentStatusHelper,
            $loggerBuilder,
            $transactionFactory
        );
    }

    /**
     * @param CaptureItemInterface $capturedItem
     * @throws LocalizedException
     * @throws MessageLocalizedException
     */
    public function processMessage(CaptureItemInterface $capturedItem)
    {
        $orderId = $capturedItem->getOrderId();

        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $e) {
            return;
        } catch (\Exception $e) {
            throw $e;
        }

        $order->setData(
            DelayPaymentOrder::IS_APPLIED_DELAY_PAYMENT_POINT,
            $capturedItem->getIsAppliedDelayPaymentPoint()
        );

        $this->getLogger()->info(__('Start to capture the order #%1', $order->getIncrementId()));

        if ($order->canInvoice()) {
            try {
                $this->prepareOrder($order);
                $invoice = $this->capture($order);
                $this->captureSuccessfullyCallback($order, $invoice);

                $this->getLogger()->info(__('SUCCESS : %1', $order->getIncrementId()));
            } catch (DelayPaymentReAuthorizeException $e) {
                $this->getLogger()->error(__(
                    'REAUTHORIZE DELAY PAYMENT FAILED : #%1 : %2',
                    $order->getIncrementId(),
                    $e->getMessage()
                ));
            } catch (DelayPaymentSaveReAuthorizeDataException $e) {
                $message = __(
                    'SAVE REAUTHORIZE DATA DELAY PAYMENT FAILED : #%1 : %2',
                    $order->getIncrementId(),
                    $e->getMessage()
                );
                $this->getLogger()->error($message);

                $this->requeueAppliedUsePointDelayPaymentOrder($order);

                throw new LocalizedException($message);
            } catch (PaygentCaptureException $e) {
                $this->getLogger()->error(__('CAPTURE FAILED : #%1 : %2', $order->getIncrementId(), $e->getMessage()));
                $this->captureFailureCallback($order);
            } catch (\Exception $e) {
                $this->getLogger()->critical($e);
                if ($order->getTotalDue()) {
                    $this->getLogger()->error(__(
                        'CAPTURE ERROR : #%1 : %2',
                        $order->getIncrementId(),
                        $e->getMessage()
                    ));

                    throw new MessageLocalizedException(__(
                        'CAPTURE ERROR : %1 : %2',
                        $order->getIncrementId(),
                        $e->getMessage()
                    ));
                }

                $this->getLogger()->error(__(
                    'UPDATE ORDER ERROR : %1 : %2',
                    $order->getIncrementId(),
                    $e->getMessage()
                ));

                $this->publishUpdateOrderFailedMessage($order);
            }
        } else {
            $this->getLogger()->info(__('Can not create invoice #%1', $order->getIncrementId()));
        }

        $this->queueLock->deleteLock(self::CAPTURE_ORDER_QUEUE_NAME, $order->getId());

        return;
    }

    /**
     * @param Order $order
     * @return $this
     * @throws LocalizedException
     */
    protected function publishUpdateOrderFailedMessage(Order $order)
    {
        /** @var FailureItemInterface $messageItem */
        $messageItem = $this->failureItemInterfaceFactory->create();
        $messageItem->setEntityId($order->getId())
            ->setExecutor(FailureOrderCapture::FAILURE_CAPTURE_EXECUTOR_NAME);

        $this->publisher->publish(Failure::FAILURE_TOPIC_NAME, $messageItem);

        $this->queueLock->lock(Failure::FAILURE_TOPIC_NAME, $messageItem->getEntityId(), $messageItem->getExecutor());

        return $this;
    }

    /**
     * @param Order $order
     * @return $this
     */
    protected function requeueAppliedUsePointDelayPaymentOrder(Order $order)
    {
        /** @var CaptureItemInterface $messageItem */
        $messageItem = $this->captureItemInterfaceFactory->create();
        $messageItem->setOrderId($order->getId())
            ->setIsAppliedDelayPaymentPoint(true);

        $this->publisher->publish(self::CAPTURE_ORDER_QUEUE_NAME, $messageItem);

        return $this;
    }

    /**
     * @param Order $order
     * @return $this
     * @throws \Exception
     */
    public function captureFailureCallback(
        Order $order
    ) {
        $order->setPaymentStatus(
            PaymentStatus::SHIPPING_PAYMENT_STATUS_CAPTURE_FAILED
        )->setStatus(
            OrderStatus::STATUS_ORDER_CAPTURE_FAILED
        )->setState(
            Order::STATE_PROCESSING
        )->addStatusHistoryComment(__('Paygent captured unsuccessfully'));

        //Use transaction to handle plugins before/after function Order::save()
        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();

        $transaction->addObject($order);

        $transaction->save();

        return $this;
    }

    /**
     * Need to re-authorize first for DELAY PAYMENT order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return $this
     * @throws LocalizedException
     */
    protected function prepareOrder(Order $order)
    {
        if ($order->getRikiType() == \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT) {
            $this->delayPaymentOrderFactory->create($order)->prepare();
        }

        return $this;
    }

    /**
     * Add subfix to logger file name to save log for each consumer to single file
     *
     * @return \Riki\Framework\Helper\Logger\Monolog
     * @throws \Exception
     */
    private function getLogger()
    {
        if (!$this->logger) {
            $this->logger = $this->createLogger('capture_' . rand(100000, 999999));
        }

        return $this->logger;
    }
}
