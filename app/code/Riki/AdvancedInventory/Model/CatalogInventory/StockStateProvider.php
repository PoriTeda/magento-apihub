<?php

namespace Riki\AdvancedInventory\Model\CatalogInventory;

use Magento\CatalogInventory\Api\Data\StockItemInterface;

class StockStateProvider extends \Riki\CatalogInventory\Model\StockStateProvider
{
    protected $checkedData = [];

    /**
     * get checked data
     *
     * @return array
     */
    public function getCheckedData()
    {
        return $this->checkedData;
    }

    /**
     * set checked data
     *
     * @param $key
     * @param $value
     */
    public function setCheckedData($key, $value)
    {
        $this->checkedData[$key] = $value;
    }

    /**
     * @param StockItemInterface $stockItem
     * @param int|float|array $qty
     * @param int|float $summaryQty
     * @param int|float $origQty
     * @return \Magento\Framework\DataObject
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function checkQuoteItemQty(StockItemInterface $stockItem, $qty, $summaryQty, $origQty = 0)
    {
        $productId = $stockItem->getProductId();

        $key = $productId . '-' . $qty;

        if (!isset($this->checkedData[$key])) {
            $availableStatus = $this->assignation->checkAvailabilityForCartItem(
                $stockItem->getProductId(),
                $this->pointOfSaleManagement->getPlaceIds(),
                $qty
            );

            $this->setCheckedData($key, $availableStatus['status']);
        }

        if ($this->checkedData[$key] < \Riki\AdvancedInventory\Model\Assignation::STOCK_STATUS_AVAILABLE_BACK_ORDER) {
            $result = $this->objectFactory->create();
            $message = __('We don\'t have as many "%1" as you requested.', $stockItem->getProductName());
            $result->setHasError(true)
                ->setMessage($message)
                ->setQuoteMessage($message)
                ->setQuoteMessageIndex('qty' . $productId);
            return $result;
        }

        return parent::checkQuoteItemQty($stockItem, $qty, $summaryQty, $origQty);
    }
}
