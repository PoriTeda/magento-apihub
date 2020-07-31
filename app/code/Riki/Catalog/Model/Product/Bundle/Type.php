<?php
/**
 * Catalog.
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Catalog
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Catalog\Model\Product\Bundle;

use Magento\Bundle\Model\ResourceModel\Selection\Collection\FilterApplier as SelectionCollectionFilterApplier;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Bundle\Model\ResourceModel\Selection\CollectionFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Type.
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Catalog
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Type extends \Magento\Bundle\Model\Product\Type
{
    /**
     * StockFactory
     *
     * @var \Wyomind\AdvancedInventory\Model\StockFactory
     */
    protected $stockFactory;

    /**
     * PointOfSaleFactory
     *
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $posFactory;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    public function __construct(
        \Magento\Catalog\Model\Product\Option $catalogProductOption,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Catalog\Model\Product\Type $catalogProductType,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Registry $coreRegistry,
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Helper\Product $catalogProduct,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Bundle\Model\SelectionFactory $bundleModelSelection,
        \Magento\Bundle\Model\ResourceModel\BundleFactory $bundleFactory,
        \Magento\Bundle\Model\ResourceModel\Selection\CollectionFactory $bundleCollection,
        \Magento\Catalog\Model\Config $config,
        \Magento\Bundle\Model\ResourceModel\Selection $bundleSelection,
        \Magento\Bundle\Model\OptionFactory $bundleOption,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Wyomind\AdvancedInventory\Model\StockFactory $stockFactory,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory,
        Json $serializer = null,
        MetadataPool $metadataPool = null,
        SelectionCollectionFilterApplier $selectionCollectionFilterApplier = null
    ) {
        $this->stockFactory = $stockFactory;
        $this->posFactory = $posFactory;
        parent::__construct($catalogProductOption, $eavConfig, $catalogProductType, $eventManager, $fileStorageDb,
            $filesystem, $coreRegistry, $logger, $productRepository, $catalogProduct, $catalogData,
            $bundleModelSelection, $bundleFactory, $bundleCollection, $config, $bundleSelection, $bundleOption,
            $storeManager, $priceCurrency, $stockRegistry, $stockState, $serializer, $metadataPool,
            $selectionCollectionFilterApplier);
    }

    /**
     * IsSalable
     *
     * @param \Magento\Catalog\Model\Product $product Product
     *
     * @return bool|mixed
     */
    public function isSalable($product)
    {
        if ($product->hasData('all_items_salable')) {
            return $product->getData('all_items_salable');
        }
        //check condition warehouse for bundle
        $checkBundleWarehouse = $this->checkWarehouseBundle($product, 1);

        $isSalable = $checkBundleWarehouse['error'];

        $product->setData('all_items_salable', $isSalable);

        return $isSalable;
    }

    /**
     * Check warehouse bundle , all item same warehouse
     *
     * @param \Magento\Catalog\Model\Product $product Product
     * @param int                            $qty     Int
     *
     * @return bool
     */
    public function checkWarehouseBundle($product,$qty)
    {
        $bundleCheck = ['error' => true,'error_type' => ""];
        $optionCollection = $this->getOptionsCollection($product);

        if (!count($optionCollection->getItems())) {
            $bundleCheck['error'] = false;
            $bundleCheck;
        }

        $selectionCollection = $this->getSelectionsCollection($optionCollection->getAllIds(), $product);

        if (!count($selectionCollection->getItems())) {
            $bundleCheck['error'] = false;
            return $bundleCheck;
        }

        //get warehouse
        $placeIds = $this->getAllPlaceId();

        return $this->checkWarehouseBundleByPlaceIds($selectionCollection, $placeIds, $qty);


    }

    /**
     * check bundle product can add to cart
     *
     * @param $bundleChildrenData
     * @param $placeIds
     * @param $qty
     * @return array [
     *          'error' => boolean (true: can add to cart, false: can not add to cart - inherit property),
     *          'error_type' => "" (inherit property)
     *          'mini_qty' => int (inherit property)
     *      ]
     */
    public function checkWarehouseBundleByPlaceIds($bundleChildrenData, $placeIds, $qty)
    {
        $rs = [];
        $rs['error'] = true;
        $rs['error_type'] = "";
        $rs['mini_qty'] = 0;

        foreach ($placeIds as $id) {

            $stockValidate = $this->checkWarehouseBundleByPlaceId($bundleChildrenData, $id, $qty);

            /*product is in stock for any place id*/
            if ($stockValidate['error']) {
                $rs['error'] = true;
                $rs['error_type'] = 'bundle';
                break;
            }

            $rs['error'] = false;
            $rs['error_type'] = $stockValidate['error_type'];
            $rs['mini_qty'] = $stockValidate['mini_qty'];
        }

        return $rs;
    }

    /**
     * check bundle stock status for specific place id, based on qty
     *
     * @param $bundleChildrenData
     * @param $placeId
     * @param $qty
     * @return array [
     *          'error' => boolean (true: can add to cart with current $qty, false: cannot add current $qty to cart)
     *          'error_type' => '' {inherit from checkWarehouseBundleByPlaceIds}
     *          'mini_qty' => '' {inherit from checkWarehouseBundleByPlaceIds}
     *       ]
     */
    public function checkWarehouseBundleByPlaceId($bundleChildrenData, $placeId, $qty)
    {
        $rs = [];
        $rs['error'] = true;
        $rs['error_type'] = '';
        $rs['mini_qty'] = 0;

        $miniQty = $qty;

        foreach ($bundleChildrenData as $selection) {

            /*child product is salable*/
            if ($selection->isSalable()) {

                /*product stock data*/
                $stock = $this->stockFactory->create()->getStockSettingsByPlaceIdAndStoreId(
                    $selection->getId(),
                    $placeId,
                    [],
                    false,
                    $selection->getStoreId()
                );

                /*total qty which child product will be add to cart*/
                $needQty = $selection->getSelectionQty() * $qty;

                // Stock for sub items
                $stockItem = $this->_stockRegistry->getStockItem(
                    $selection->getId(), $selection->getStore()->getWebsiteId()
                );

                /*child item is pre-order product - wrong config*/
                if ($stockItem->getBackorders() == \Riki\Preorder\Helper\Data::BACKORDERS_PREORDER_OPTION) {
                    $rs['error'] = false;
                    break;
                }

                /*min qty which child product must be add to cart - child product config*/
                $minSaleQty = $stockItem->getMinSaleQty();

                if ($minSaleQty > $miniQty) {
                    $miniQty =  $minSaleQty;
                }

                /*quantity in stock is not enough to assign*/
                if ($stock->getQuantityInStock() < $needQty) {
                    /*not allowed back order for this warehouse*/
                    if (!$stock->getBackorderableAtStockLevel()) {
                        $rs['error'] = false;
                        break;
                    }

                    /*warehouse quantity - include back order limit*/
                    $backOrderQuantity = (int) $stock->getQuantityInStock() + (int) $stock->getBackorderLimitInStock();

                    /*quantity (include back order limit - is not enough)*/
                    if ($backOrderQuantity < $needQty) {
                        $rs['error'] = false;
                        break;
                    }
                }
            } else {
                $rs['error'] = false;
                break;
            }
        }
        /*Check sub product bundle minimum qty for bundle product - inherit logic*/
        if ($miniQty > $qty) {
            $rs['error'] = false;
            $rs['error_type'] = 'sub_bundle';
            $rs['mini_qty'] = $miniQty;
        }

        return $rs;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $stock
     * @param $qty
     * @return bool
     */
    public function checkWarehousePieceCase(\Magento\Catalog\Model\Product $product,$stock,$qty)
    {
        if ( (intval($stock->getQty()) - intval($stock->getMinQty()) - intval($qty)) < 0 ) {
            return false;
        }

        $unitQty = $product->getUnitQty() ? (int)$product->getUnitQty() : 1;
        //get warehouse
        $qtyCanOrdered = 0;
        $placeId = $this->getAllPlaceId();
        foreach ($placeId as $id) {
            $stockPlace = $this->stockFactory->create()->getStockByProductIdAndPlaceId($product->getId(), $id);

            if ($stockPlace->getQuantityInStock()) {
                $qtyCanOrdered += (int)($stockPlace->getQuantityInStock() / $unitQty);
            }
        }

        if ($qtyCanOrdered * $unitQty < $qty) {
            return false;
        }

        return true;
    }

    protected $placeId = null;

    /**
     * GetAllPlaceId
     *
     * @return array|null
     */
    public function getAllPlaceId()
    {
        if ($this->placeId === null) {
            $placeId = [];
            $places = $this->posFactory->create()->getPlaces();
            foreach ($places as $place) {
                $placeId[] = $place->getPlaceId();
            }
            $this->placeId = $placeId;
        }

        return $this->placeId;
    }

    /**
     * Return product sku based on sku_type attribute
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getSku($product)
    {
        $sku = \Magento\Catalog\Model\Product\Type\AbstractType::getSku($product);

        if ($product->getData('sku_type')) {
            return $sku;
        } else {
            $skuParts = [$sku];

            if ($product->hasCustomOptions()) {
                $customOption = $product->getCustomOption('bundle_selection_ids');
                if($customOption){
                    $selectionIds = $this->serializer->unserialize($customOption->getValue());
                    if (!empty($selectionIds)) {
                        $selections = $this->getSelectionsByIds($selectionIds, $product);
                        $aSelections = $selections->getItems();
                        foreach ($aSelections as $selection) {
                            $skuParts[] = $selection->getSku();
                        }
                    }
                }
            }

            return implode('-', $skuParts);
        }
    }

    /**
     * get stock registry properties
     *
     * @return \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    public function getStockRegistry()
    {
        return $this->_stockRegistry;
    }

    /**
     * Check is virtual product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function isVirtual($product)
    {
        if ($product->hasCustomOptions()) {
            $customOption = $product->getCustomOption('bundle_selection_ids');
            $selectionIds = $this->serializer->unserialize($customOption->getValue());
            $selections = $this->getSelectionsByIds($selectionIds, $product);
            $virtualCount = 0;
            foreach ($selections->getItems() as $selection) {
                if ($selection->isVirtual()) {
                    $virtualCount++;
                }
            }

            // custom for case OOS bundle change product options
            if ($virtualCount == 0 && $this->_coreRegistry->registry('current_oos_generating')) {
                return false;
            }
            //

            return $virtualCount == count($selections);
        }

        return false;
    }

    /**
     * Check if product can be bought
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkProductBuyState($product)
    {
        if ($this->_coreRegistry->registry('skip_validate_by_oos_order_generating')) {
            return $this;
        }
        $this->checkProductRequiredOptions($product);
        $productOptionIds = $this->getOptionsIds($product);
        $productSelections = $this->getSelectionsCollection($productOptionIds, $product);

        $selectionIds = $product->getCustomOption('bundle_selection_ids');
        if (!$selectionIds) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Some of the selected options are not currently available.')
            );
        }
        $selectionIds = $this->serializer->unserialize($selectionIds->getValue());
        $buyRequest = $product->getCustomOption('info_buyRequest');
        $buyRequest = new \Magento\Framework\DataObject($this->serializer->unserialize($buyRequest->getValue()));
        $bundleOption = $buyRequest->getBundleOption();

        if (empty($bundleOption)) {
            throw new \Magento\Framework\Exception\LocalizedException($this->getSpecifyOptionMessage());
        }

        $skipSaleableCheck = $this->_catalogProduct->getSkipSaleableCheck();
        foreach ($selectionIds as $selectionId) {
            /* @var $selection \Magento\Bundle\Model\Selection */
            $selection = $productSelections->getItemById($selectionId);
            if (!$selection || !$selection->isSalable() && !$skipSaleableCheck) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The required options you selected are not available.')
                );
            }
        }

        $product->getTypeInstance()
            ->setStoreFilter($product->getStoreId(), $product);
        $optionsCollection = $this->getOptionsCollection($product);
        foreach ($optionsCollection->getItems() as $option) {
            if ($option->getRequired() && empty($bundleOption[$option->getId()])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please select all required options.'));
            }
        }

        return $this;
    }

    /**
     * Check product required options
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkProductRequiredOptions($product)
    {
        if (!$product->getSkipCheckRequiredOption() && $product->getHasOptions()) {
            $options = $product->getProductOptionsCollection();
            foreach ($options as $option) {
                if ($option->getIsRequire()) {
                    $customOption = $product->getCustomOption(self::OPTION_PREFIX . $option->getId());
                    if (!$customOption || strlen($customOption->getValue()) == 0) {
                        $product->setSkipCheckRequiredOption(true);
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('The product has required options.')
                        );
                    }
                }
            }
        }
        return $this;
    }
}
