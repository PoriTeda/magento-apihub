<?php
namespace Riki\CatalogInventory\Model;

use Magento\CatalogInventory\Model\StockRegistryStorage;

class StockRegistryProvider extends \Magento\CatalogInventory\Model\StockRegistryProvider
{
    const UNREGISTER_STOCK_ITEM = 'catalog_inventory_unregister_stock_item';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * StockRegistryProvider constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\CatalogInventory\Api\StockRepositoryInterface $stockRepository
     * @param \Magento\CatalogInventory\Api\Data\StockInterfaceFactory $stockFactory
     * @param \Magento\CatalogInventory\Api\StockItemRepositoryInterface $stockItemRepository
     * @param \Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory $stockItemFactory
     * @param \Magento\CatalogInventory\Api\StockStatusRepositoryInterface $stockStatusRepository
     * @param \Magento\CatalogInventory\Api\Data\StockStatusInterfaceFactory $stockStatusFactory
     * @param \Magento\CatalogInventory\Api\StockCriteriaInterfaceFactory $stockCriteriaFactory
     * @param \Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory
     * @param \Magento\CatalogInventory\Api\StockStatusCriteriaInterfaceFactory $stockStatusCriteriaFactory
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\CatalogInventory\Api\StockRepositoryInterface $stockRepository,
        \Magento\CatalogInventory\Api\Data\StockInterfaceFactory $stockFactory,
        \Magento\CatalogInventory\Api\StockItemRepositoryInterface $stockItemRepository,
        \Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory $stockItemFactory,
        \Magento\CatalogInventory\Api\StockStatusRepositoryInterface $stockStatusRepository,
        \Magento\CatalogInventory\Api\Data\StockStatusInterfaceFactory $stockStatusFactory,
        \Magento\CatalogInventory\Api\StockCriteriaInterfaceFactory $stockCriteriaFactory,
        \Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory,
        \Magento\CatalogInventory\Api\StockStatusCriteriaInterfaceFactory $stockStatusCriteriaFactory
    ) {
        $this->registry = $registry;
        parent::__construct(
            $stockRepository,
            $stockFactory,
            $stockItemRepository,
            $stockItemFactory,
            $stockStatusRepository,
            $stockStatusFactory,
            $stockCriteriaFactory,
            $stockItemCriteriaFactory,
            $stockStatusCriteriaFactory
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param int $productId
     * @param int $scopeId
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    public function getStockItem($productId, $scopeId)
    {
        // should control registry stock item
        if ($productId == $this->registry->registry(static::UNREGISTER_STOCK_ITEM)) {
            $this->getStockRegistryStorage()->removeStockItem($productId, $scopeId);
            $this->registry->unregister(static::UNREGISTER_STOCK_ITEM);
        }

        return parent::getStockItem($productId, $scopeId);
    }

    /**
     * @return StockRegistryStorage
     */
    private function getStockRegistryStorage()
    {
        if (null === $this->stockRegistryStorage) {
            $this->stockRegistryStorage = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(StockRegistryStorage::class);
        }
        return $this->stockRegistryStorage;
    }
}