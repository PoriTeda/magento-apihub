<?php
/**
 * Stock
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Stock
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Stock\Model;


use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\CatalogInventory\Api\StockCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockRepositoryInterface;
use Magento\Framework\Exception\InputException;

/**
 * Stock
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Stock
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class StockRegistry implements \Riki\Stock\Api\StockRegistryInterface
{
    /**
     * Object StockConfigurationInterface
     *
     * @var StockConfigurationInterface
     */
    protected $stockConfiguration;

    /**
     * Object StockRegistryProviderInterface
     *
     * @var StockRegistryProviderInterface
     */
    protected $stockRegistryProvider;

    /**
     * Object ProductFactory
     *
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * Object StockItemRepositoryInterface
     *
     * @var StockItemRepositoryInterface
     */
    protected $stockItemRepository;

    /**
     * Object StockItemCriteriaInterfaceFactory
     *
     * @var \Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory
     */
    protected $criteriaFactory;

    /**
     * Object StockCriteriaInterfaceFactory
     *
     * @var StockCriteriaInterfaceFactory
     */
    protected $stockCriteriaFactory;

    /**
     * Object StockRepositoryInterface
     *
     * @var StockRepositoryInterface
     */
    protected $stockRepository;

    /**
     * Object StockItemCriteriaInterfaceFactory
     *
     * @var \Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory
     */
    protected $stockItemCriteriaFactory;

    /**
     * Object Stock
     *
     * @var \Wyomind\AdvancedInventory\Model\Stock
     */
    protected $collectionStockWyomind;

    /**
     * Obhect StockItemCollection
     *
     * @var \Riki\Stock\Model\StockItemCollection
     */
    protected $listItem;

    /**
     * Object StockItemWrapperFactory
     *
     * @var \Riki\Stock\Model\StockItemWrapperFactory
     */
    protected $stockItemWrapperFactory;

    /**
     * Object PointOfSale
     *
     * @var \Wyomind\PointOfSale\Model\PointOfSale
     */
    protected $pointOfSale;

    /**
     * Object Product
     *
     * @var \Magento\Catalog\Model\Product
     */
    protected $modelProduct;
    /**
     * @var \Riki\MachineApi\Helper\Data
     */
    protected $machineHelper;

    /**
     * StockRegistry constructor.
     *
     * @param StockConfigurationInterface $stockConfiguration
     * @param StockRegistryProviderInterface $stockRegistryProvider
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $criteriaFactory
     * @param ProductFactory $productFactory
     * @param StockCriteriaInterfaceFactory $stockCriteriaFactory
     * @param StockRepositoryInterface $stockRepository
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory
     * @param \Wyomind\AdvancedInventory\Model\Stock $collectionStockWyomind
     * @param StockItemCollection $listItem
     * @param StockItemWrapperFactory $stockItemWrapperFactory
     * @param \Wyomind\PointOfSale\Model\PointOfSale $pointOfSale
     * @param \Magento\Catalog\Model\Product $modelProduct
     * @param \Riki\MachineApi\Helper\Data $machineHelper
     */
    public function __construct(
        StockConfigurationInterface $stockConfiguration,
        StockRegistryProviderInterface $stockRegistryProvider,
        StockItemRepositoryInterface $stockItemRepository,
        StockItemCriteriaInterfaceFactory $criteriaFactory,
        ProductFactory $productFactory,
        StockCriteriaInterfaceFactory $stockCriteriaFactory,
        StockRepositoryInterface $stockRepository,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory,
        \Wyomind\AdvancedInventory\Model\Stock  $collectionStockWyomind,
        \Riki\Stock\Model\StockItemCollection $listItem,
        \Riki\Stock\Model\StockItemWrapperFactory $stockItemWrapperFactory,
        \Wyomind\PointOfSale\Model\PointOfSale $pointOfSale,
        \Magento\Catalog\Model\Product $modelProduct,
        \Riki\MachineApi\Helper\Data $machineHelper
    ) {
        $this->stockConfiguration = $stockConfiguration;
        $this->stockRegistryProvider = $stockRegistryProvider;
        $this->stockItemRepository = $stockItemRepository;
        $this->criteriaFactory = $criteriaFactory;
        $this->productFactory = $productFactory;
        $this->listItem = $listItem;
        $this->stockCriteriaFactory = $stockCriteriaFactory;
        $this->stockRepository = $stockRepository;
        $this->stockItemCriteriaFactory = $stockItemCriteriaFactory;
        $this->collectionStockWyomind   = $collectionStockWyomind;
        $this->stockItemWrapperFactory = $stockItemWrapperFactory;
        $this->pointOfSale = $pointOfSale;
        $this->modelProduct = $modelProduct;
        $this->machineHelper = $machineHelper;
    }

    /**
     * Get Stocl item By Sku
     *
     * @param \string[] $products products
     *
     * @return mixed
     *
     * @throws InputException
     */
    public function getStockItemBySku($products)
    {
        if (is_array($products)&&count($products)>0) {
            $dataTmp = array();

            /*load code default warehouse for machine*/
            $codeWareHouse = $this->pointOfSale->load(
                $this->machineHelper->getMachineDefaultPlace()
            )->getData('store_code');

            foreach ($products as $skuItem) {

                 $scopeId   = $this->stockConfiguration->getDefaultScopeId();

                /**
                 * Object product item
                 *
                 * @var \Magento\Catalog\Model\Product $productItem productItem
                 */
                $productItem = $this->resolveProductId($skuItem, $scopeId);

                if ($productItem) {

                    $productId = $productItem->getId();

                    $itemStock =$this->stockRegistryProvider->getStockItem($productId, $scopeId);

                    $productWareHouse =$this->getWareHouser(
                        $productId,
                        $this->machineHelper->getMachineDefaultPlace()
                    );

                    if ($productWareHouse) {

                        $productWareHouse->setPos($codeWareHouse);

                        $dataTmp[] = $this->getProductDataForMachine(
                            trim($skuItem),
                            $itemStock,
                            $productWareHouse
                        );
                    }
                }
            }

            $stockItemWrapper = $this->stockItemWrapperFactory->create();
            $stockItemWrapper->setProducts($dataTmp);
            return $stockItemWrapper;
        } else {
            throw new \Magento\Framework\Exception\InputException(__('SKU is not available.'));
        }
    }

    /**
     * Resolve product id
     *
     * @param string $productSku productSku
     *
     * @return \Magento\Framework\DataObject
     *
     * @throws InputException
     */
    protected function resolveProductId($productSku)
    {
        $product = $this->modelProduct->getCollection()
            ->addFieldToFilter('sku', $productSku)
            ->setPageSize(1)
            ->setCurPage(1);
        if ($product && $product->getSize()>0) {
            return $product->getFirstItem();
        }
        return null;
    }

    /**
     * get product data from specific warehouse
     *
     * @param $productId
     * @param $warehouseId
     * @return bool|\Magento\Framework\DataObject
     */
    public function getWareHouser($productId,$warehouseId)
    {
        $enableStock = $this->collectionStockWyomind->isMultiStockEnabledByProductId($productId);
        if ($enableStock) {
            $dataWareHouse =  $this->collectionStockWyomind->getCollection();
            $dataWareHouse->addFieldToFilter(
                'product_id', $productId
            )->addFieldToFilter(
                'place_id', $warehouseId
            )->setPageSize(1)->setCurPage(1);

            if ($dataWareHouse->getSize()) {
                return $dataWareHouse->getFirstItem();
            }
        }
        return false;
    }

    /**
     * Get product data for machine api
     *
     * @param $sku
     * @param $itemStock
     * @param $productWarehouse
     * @return array
     */
    public function getProductDataForMachine($sku, $itemStock, $productWarehouse)
    {
        $quantityInStock = $productWarehouse->getQuantityInStock();

        if ($productWarehouse->getBackorderAllowed() == 1) {
            $quantityInStock += (int) $productWarehouse->getBackorderLimit();
        }

        if ($quantityInStock <= 0) {
            $quantityInStock = 0;
            $itemStock->setIsInstock(false);
        }

        /*allowed back order, unlimited stock*/
        if ($productWarehouse->getBackorderAllowed() == 1
            && $productWarehouse->getBackorderLimit() == 0
        ) {
            $quantityInStock = 0;
            $itemStock->setIsInstock(true);
        }

        /*disabled warehouse*/
        if (!$productWarehouse->getManageStock()) {
            $quantityInStock = 0;
            $itemStock->setIsInstock(false);
        }

        $itemStock->setQty($quantityInStock);

        return array(
            "item_id"     => $itemStock->getItemId(),
            "warehouse"   => $productWarehouse->getPos(),
            "product_id"  => $itemStock->getProductId(),
            "product_sku" => $sku,
            "qty"         => $itemStock->getQty(),
            "is_in_stock" => $itemStock->getIsInStock(),
            "min_sale_qty"=> $itemStock->getMinSaleQty(),
            "max_sale_qty"=> $itemStock->getMaxSaleQty()
        );
    }
}