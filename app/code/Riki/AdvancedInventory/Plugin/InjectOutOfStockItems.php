<?php

namespace Riki\AdvancedInventory\Plugin;

use Riki\AdvancedInventory\Plugin\Quote\Model\Quote\OosTotalsCollector;

class InjectOutOfStockItems
{
    /**
     * @var array
     */
    protected $_whitelist = [
        \Magento\SalesRule\Model\Quote\Discount::class,
        \Riki\Loyalty\Model\Total\Quote\PointEarn::class,
    ];

    /**
     * @var \Riki\AdvancedInventory\Model\OutOfStock\ItemInjector
     */
    protected $outOfStockItemInjector;

    /**
     * InjectOutOfStockItems constructor.
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock\ItemInjector $outOfStockItemInjector
     */
    public function __construct(
        \Riki\AdvancedInventory\Model\OutOfStock\ItemInjector $outOfStockItemInjector
    )
    {
        $this->outOfStockItemInjector = $outOfStockItemInjector;
    }

    /**
     * Inject out of stock items into shipping assignment,
     * then collectors can calculate total base on out of stock items.
     *
     * @param $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return mixed
     */
    public function aroundCollect(
        $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        if (!$quote->getData(OosTotalsCollector::OOS_COLLECT_TOTALS)
            && $this->isInWhitelist($subject)
        ) {
            $items = $shippingAssignment->getItems();

            if (count($items)) {
                $oosIncludedItems = $this->outOfStockItemInjector->inject($quote, $items);

                $shippingAssignment->setItems($oosIncludedItems);
                $result = $proceed($quote, $shippingAssignment, $total);
                $shippingAssignment->setItems($items);

                return $result;
            }
        }

        return $proceed($quote, $shippingAssignment, $total);
    }

    /**
     * Check object is a instance in the whitelist
     *
     * @param $object
     *
     * @return bool
     */
    protected function isInWhitelist($object)
    {
        foreach ($this->_whitelist as $class) {
            if ($object instanceof $class) {
                return true;
            }
        }

        return false;
    }
}
