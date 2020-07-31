<?php
namespace Riki\AdvancedInventory\Plugin\AdvancedInventory\Model\OutOfStock;

class GeneratedOrder
{
    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock\Order
     */
    protected $outOfStockOrderHelper;

    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock
     */
    protected $outOfStockHelper;

    /**
     * CvsOrder constructor.
     *
     * @param \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
     * @param \Riki\AdvancedInventory\Helper\OutOfStock\Order $outOfStockOrderHelper
     */
    public function __construct(
        \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper,
        \Riki\AdvancedInventory\Helper\OutOfStock\Order $outOfStockOrderHelper
    ) {
        $this->outOfStockHelper = $outOfStockHelper;
        $this->outOfStockOrderHelper = $outOfStockOrderHelper;
    }

    /**
     * Import payment cvs order
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $subject
     * @param \Riki\AdvancedInventory\Model\OutOfStock $result
     *
     * @return \Riki\AdvancedInventory\Model\OutOfStock
     */
    public function afterAfterSave(
        \Riki\AdvancedInventory\Model\OutOfStock $subject,
        \Riki\AdvancedInventory\Model\OutOfStock $result
    ) {
        if (!$result->dataHasChangedFor('generated_order_id')) {
            return $result;
        }

        $this->outOfStockOrderHelper->processCvsPayment($result);
        $this->outOfStockOrderHelper->processPaygentPayment($result);

        return $result;
    }
}