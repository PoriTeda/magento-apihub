<?php
namespace Riki\Checkout\Plugin\CatalogInventory\Model\StockStateProvider;

class MultiShippingQty
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * MultiShippingQty constructor.
     *
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * used multi_shipping_qty in case multi checkout
     *
     * @param \Magento\CatalogInventory\Model\StockStateProvider $subject
     * @param \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
     * @param $qty
     * @param $summaryQty
     * @param int $origQty
     * @return array
     */
    public function beforeCheckQuoteItemQty(
        \Magento\CatalogInventory\Model\StockStateProvider $subject,
        \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem,
        $qty,
        $summaryQty,
        $origQty = 0
    ) {
        $key = \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator::class . '::validate';
        $registryData = $this->registry->registry($key);
        if (!$registryData || !isset($registryData['observer'])) {
            return [$stockItem, $qty, $summaryQty, $origQty];
        }

        $observer = $registryData['observer'];
        if (!$observer instanceof \Magento\Framework\Event\Observer) {
            return [$stockItem, $qty, $summaryQty, $origQty];
        }

        $item = $observer->getData('item');
        if (!$item instanceof \Magento\Quote\Model\Quote\Item) {
            return [$stockItem, $qty, $summaryQty, $origQty];
        }

        if ($item->hasData('multi_shipping_qty')
            && floatval($item->getData('qty')) == $qty
            && floatval($item->getData('multi_shipping_qty')) > $qty
            /*item qty must be less than stockitem max sale qty*/
            && floatval($item->getData('multi_shipping_qty')) <= $stockItem->getMaxSaleQty()
        ) {
            $qty = floatval($item->getData('multi_shipping_qty'));
        }

        return [$stockItem, $qty, $summaryQty, $origQty];
    }
}