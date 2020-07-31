<?php
namespace Riki\Checkout\Model;

use \Riki\CatalogInventory\Model\StockRegistryProvider;

/**
 * Class StockManagement
 */
class StockManagement extends \Magento\CatalogInventory\Model\StockManagement
{

    /**
     * @var \Magento\CatalogInventory\Model\ResourceModel\QtyCounterInterface
     */
    protected $qtyCounter;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * StockManagement constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock $stockResource
     * @param \Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface $stockRegistryProvider
     * @param \Magento\CatalogInventory\Model\StockState $stockState
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\CatalogInventory\Model\ResourceModel\QtyCounterInterface $qtyCounter
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\Registry $registry,
        \Magento\CatalogInventory\Model\ResourceModel\Stock $stockResource,
        \Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface $stockRegistryProvider,
        \Magento\CatalogInventory\Model\StockState $stockState,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\CatalogInventory\Model\ResourceModel\QtyCounterInterface $qtyCounter
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->functionCache = $functionCache;
        $this->qtyCounter = $qtyCounter;
        $this->registry = $registry;
        parent::__construct(
            $stockResource,
            $stockRegistryProvider,
            $stockState,
            $stockConfiguration,
            $productRepository,
            $qtyCounter
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param \string[] $items
     * @param null $websiteId
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function registerProductsSale($items, $websiteId = null)
    {
        return [];
    }

    /**
     * Get allow create oos spot order at admin area
     *
     * @return bool
     */
    public function getAllowOosSpotOrderAtAdminArea()
    {
        return (bool)$this->scopeConfig->getValue(
            'cataloginventory/order_options/allow_create_order_out_of_stock',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param string[] $items
     * @param null $websiteId
     *
     * @return bool
     */
    public function revertProductsSale($items, $websiteId = null)
    {
        return true;
    }
}
