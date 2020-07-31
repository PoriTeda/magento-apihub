<?php
namespace Riki\AdvancedInventory\Helper\OutOfStock;

use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\AdvancedInventory\Api\Data\OutOfStock\QueueExecuteInterface;

class Order
{
    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock
     */
    protected $outOfStockHelper;

    /**
     * @var \Riki\ShipmentImporter\Cron\ShipmentImporter1507
     */
    protected $shipmentImporter1507;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * @var \Bluecom\Paygent\Model\PaygentOptionFactory
     */
    protected $paygentOptionFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface
     */
    protected $orderStatusHistoryRepository;

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * Order constructor.
     *
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
     * @param \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Bluecom\Paygent\Model\PaygentOptionFactory $paygentOptionFactory
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     * @param \Magento\Framework\App\State $appState
     * @param \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
     * @param \Riki\ShipmentImporter\Cron\ShipmentImporter1507 $shipmentImporter1507
     */
    public function __construct(
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository,
        \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Bluecom\Paygent\Model\PaygentOptionFactory $paygentOptionFactory,
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Magento\Framework\App\State $appState,
        \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper,
        \Riki\ShipmentImporter\Cron\ShipmentImporter1507 $shipmentImporter1507
    ) {
        $this->outOfStockRepository = $outOfStockRepository;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->paygentOptionFactory = $paygentOptionFactory;
        $this->datetimeHelper = $datetimeHelper;
        $this->appState = $appState;
        $this->outOfStockHelper = $outOfStockHelper;
        $this->shipmentImporter1507 = $shipmentImporter1507;
    }

    /**
     * Process payment & status for order
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
     *
     * @return \Riki\AdvancedInventory\Model\OutOfStock
     */
    public function processCvsPayment(\Riki\AdvancedInventory\Model\OutOfStock $outOfStock)
    {
        if (!$outOfStock->getIsFree()) {
            return $outOfStock;
        }

        if ($this->outOfStockHelper->getPaymentMethodCode($outOfStock) != \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE) {
            return $outOfStock;
        }

        $order = $this->outOfStockHelper->getOriginalOrder($outOfStock);
        $allowedStatus = [
            OrderStatus::STATUS_ORDER_NOT_SHIPPED,
            OrderStatus::STATUS_ORDER_COMPLETE,
            OrderStatus::STATUS_ORDER_IN_PROCESSING,
            OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED,
        ];

        if (!$order || !in_array($order->getStatus(), $allowedStatus)) {
            return $outOfStock;
        }

        $generatedOrder = $this->outOfStockHelper->getGeneratedOrder($outOfStock);
        if (!$generatedOrder) {
            return $outOfStock;
        }

        if (!in_array($generatedOrder->getStatus(), [OrderStatus::STATUS_ORDER_PENDING_CVS])) {
            return $outOfStock;
        }

        $data = [
            [
            '',
            '',
            '',
            $generatedOrder->getIncrementId(),
            '',
            '',
            '',
            $this->datetimeHelper->getToday()->format('Ymd'),
            ''
            ]
        ];

        $this->appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB, [$this->shipmentImporter1507, 'importProcess'], [$data]);

        return $outOfStock;
    }

    /**
     * Process payment & status for order
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
     *
     *
     * @throws \Exception
     *
     * @return \Riki\AdvancedInventory\Model\OutOfStock
     */
    public function processPaygentPayment(\Riki\AdvancedInventory\Model\OutOfStock $outOfStock)
    {
        if ($this->outOfStockHelper->getPaymentMethodCode($outOfStock) != \Bluecom\Paygent\Model\Paygent::CODE) {
            return $outOfStock;
        }

        $order = $this->outOfStockHelper->getOriginalOrder($outOfStock);
        $allowedStatus = [
            OrderStatus::STATUS_ORDER_NOT_SHIPPED,
            OrderStatus::STATUS_ORDER_COMPLETE,
            OrderStatus::STATUS_ORDER_IN_PROCESSING,
            OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED,
        ];

        if (!$order || !in_array($order->getStatus(), $allowedStatus)) {
            return $outOfStock;
        }

        $generatedOrder = $this->outOfStockHelper->getGeneratedOrder($outOfStock);
        if (!$generatedOrder) {
            $outOfStock->pushIntoQueue();
            return $outOfStock;
        }

        if (!in_array($generatedOrder->getStatus(), [OrderStatus::STATUS_ORDER_PENDING_CC])) {
            return $outOfStock;
        }

        $payment = $generatedOrder->getPayment();
        if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
            return $outOfStock;
        }

        try {
            $paymentOrder = $payment->getOrder();
            $paymentOrder->setData(\Riki\AdvancedInventory\Model\OutOfStock::OOS_FLAG, true);
            $paymentOrder->setUseIvr(0);

            /** @var \Bluecom\Paygent\Model\PaygentOption $paygentOption */
            $paygentOption = $this->paygentOptionFactory->create()->loadByAttribute('customer_id', $generatedOrder->getCustomerId());
            $paygentOption->setData('option_checkout', 0); // authorize without redirect
            $paygentOption->save();
            $payment->place();

            $this->orderRepository->save($paymentOrder);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->info($e);
            $history = $generatedOrder->addStatusHistoryComment($e->getMessage(), OrderStatus::STATUS_ORDER_PENDING_CC);
            $this->orderStatusHistoryRepository->save($history);
        } catch (\Exception $e) {
            throw  $e;
        }

        return $outOfStock;
    }
}