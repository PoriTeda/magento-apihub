<?php
namespace Riki\CatalogInventory\Model;

class StockStateProvider extends \Magento\CatalogInventory\Model\StockStateProvider
{
    /**
     * @var \Riki\AdvancedInventory\Model\Assignation
     */
    protected $assignation;

    /**
     * @var \Riki\PointOfSale\Model\PointOfSaleManagement
     */
    protected $pointOfSaleManagement;

    /**
     * StockStateProvider constructor.
     *
     * @param \Magento\Framework\Math\Division $mathDivision
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Riki\AdvancedInventory\Model\Assignation $assignation,
     * @param \Riki\PointOfSale\Model\PointOfSaleManagement $pointOfSaleManagement,
     * @param bool $qtyCheckApplicable
     */
    public function __construct(
        \Magento\Framework\Math\Division $mathDivision,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\AdvancedInventory\Model\Assignation $assignation,
        \Riki\PointOfSale\Model\PointOfSaleManagement $pointOfSaleManagement,
        $qtyCheckApplicable = true
    ) {
        parent::__construct(
            $mathDivision,
            $localeFormat,
            $objectFactory,
            $productFactory,
            $qtyCheckApplicable
        );

        $this->assignation = $assignation;
        $this->pointOfSaleManagement = $pointOfSaleManagement;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
     * @param float|int $qty
     * @return bool
     */
    public function checkQty(\Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem, $qty)
    {
        if (!$this->qtyCheckApplicable) {
            return true;
        }

        if (!$stockItem->getManageStock()) {
            return true;
        }

        $availableStatus = $this->assignation->checkAvailabilityForCartItem(
            $stockItem->getProductId(),
            $this->pointOfSaleManagement->getPlaceIds(),
            $qty
        );

        if (empty($availableStatus) || !isset($availableStatus['status'])) {
            return false;
        }

        /*stock is not enough*/
        if ($availableStatus['status'] < \Riki\AdvancedInventory\Model\Assignation::STOCK_STATUS_AVAILABLE_BACK_ORDER) {
            return false;
        }

        return true;
    }
}