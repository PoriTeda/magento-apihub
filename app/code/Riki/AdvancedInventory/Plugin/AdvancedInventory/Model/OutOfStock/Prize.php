<?php
namespace Riki\AdvancedInventory\Plugin\AdvancedInventory\Model\OutOfStock;

class Prize
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Rule\Model\ResourceModel\OrderSapBooking
     */
    protected $orderSapBookingResource;

    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock
     */
    protected $outOfStockHelper;

    /**
     * Prize constructor.
     *
     * @param \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Rule\Model\ResourceModel\OrderSapBooking $orderSapBookingResource\
     */
    public function __construct(
        \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Rule\Model\ResourceModel\OrderSapBooking $orderSapBookingResource
    ){
        $this->outOfStockHelper = $outOfStockHelper;
        $this->logger = $logger;
        $this->orderSapBookingResource = $orderSapBookingResource;
    }

    /**
     * Save wbs for prize
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $subject
     * @param \Riki\AdvancedInventory\Model\OutOfStock $result
     *
     * @return \Riki\AdvancedInventory\Model\OutOfStock
     */
    public function afterAfterSave(
        \Riki\AdvancedInventory\Model\OutOfStock $subject,
        \Riki\AdvancedInventory\Model\OutOfStock $result
    ){
        if (!$result->dataHasChangedFor('generated_order_id')) {
            return $result;
        }

        if (!$result->getPrizeId()) {
            return $result;
        }

        $order =  $this->outOfStockHelper->getGeneratedOrder($result);
        if (!$order) {
            return $result;
        }

        $prize = $this->outOfStockHelper->getPrize($result);
        if (!$prize->getId() || !$prize->getData('wbs')) {
            return $result;
        }

        $data = [];
        foreach ($order->getItems() as $orderItem) {
            $data[] = [
                'rule_id' => '',
                'rule_type' => '',
                'type' => \Riki\Prize\Helper\Prize::WBS_TYPE,
                'value' => $prize->getData('wbs'),
                'order_id' => $orderItem->getOrderId(),
                'order_item_id' => $orderItem->getId()
            ];
        }
        if ($data) {
            $this->orderSapBookingResource->multiplyBunchInsert($data);
        }

        return $result;
    }
}