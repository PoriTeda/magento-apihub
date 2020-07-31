<?php
namespace Riki\AdvancedInventory\Model\Bundle\Product;

use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Bundle Price Model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Price extends \Magento\Bundle\Model\Product\Price
{
    /** @var \Magento\Catalog\Helper\Product  */
    protected $productHelper;

    /**
     * Price constructor.
     * @param \Magento\CatalogRule\Model\ResourceModel\RuleFactory $ruleFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param GroupManagementInterface $groupManagement
     * @param \Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory $tierPriceFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\Catalog\Helper\Product $productHelper
     */
    public function __construct(
        \Magento\CatalogRule\Model\ResourceModel\RuleFactory $ruleFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        PriceCurrencyInterface $priceCurrency,
        GroupManagementInterface $groupManagement,
        \Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory $tierPriceFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Catalog\Helper\Product $productHelper
    ) {
    
        $this->productHelper = $productHelper;

        parent::__construct(
            $ruleFactory,
            $storeManager,
            $localeDate,
            $customerSession,
            $eventManager,
            $priceCurrency,
            $groupManagement,
            $tierPriceFactory,
            $config,
            $catalogData
        );
    }

    /**
     * Get Total price  for Bundle items
     * Allowed to include OOS child price
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param null|float $qty
     * @return float
     */
    public function getTotalBundleItemsPrice($product, $qty = null)
    {
        $price = 0.0;
        if ($product->hasCustomOptions()) {
            $selectionIds = $this->getBundleSelectionIds($product);
            if ($selectionIds) {
                $selections = $product->getTypeInstance()->getSelectionsByIds($selectionIds, $product);
                $selections->addTierPriceData();
                $this->_eventManager->dispatch(
                    'prepare_catalog_product_collection_prices',
                    ['collection' => $selections, 'store_id' => $product->getStoreId()]
                );
                foreach ($selections->getItems() as $selection) {
                    if ($this->productHelper->getSkipSaleableCheck() || $selection->isSalable()) { // custom
                        $selectionQty = $product->getCustomOption('selection_qty_' . $selection->getSelectionId());
                        if ($selectionQty) {
                            $price += $this->getSelectionFinalTotalPrice(
                                $product,
                                $selection,
                                $qty,
                                $selectionQty->getValue()
                            );
                        }
                    }
                }
            }
        }
        return $price;
    }

    /**
     * Add new event to apply stock point discount for children price
     *
     * Calculate final price of selection
     * with take into account tier price
     *
     * @param  \Magento\Catalog\Model\Product $bundleProduct
     * @param  \Magento\Catalog\Model\Product $selectionProduct
     * @param  float                    $bundleQty
     * @param  float                    $selectionQty
     * @param  bool                       $multiplyQty
     * @param  bool                       $takeTierPrice
     * @return float
     */
    public function getSelectionFinalTotalPrice(
        $bundleProduct,
        $selectionProduct,
        $bundleQty,
        $selectionQty,
        $multiplyQty = true,
        $takeTierPrice = true
    ) {
        if (null === $bundleQty) {
            $bundleQty = 1.;
        }
        if ($selectionQty === null) {
            $selectionQty = $selectionProduct->getSelectionQty();
        }

        if ($bundleProduct->getPriceType() == self::PRICE_TYPE_DYNAMIC) {
            $price = $selectionProduct->getFinalPrice($takeTierPrice ? $selectionQty : 1);
        } else {
            if ($selectionProduct->getSelectionPriceType()) {
                // percent
                $product = clone $bundleProduct;
                $product->setFinalPrice($this->getPrice($product));
                $this->_eventManager->dispatch(
                    'catalog_product_get_final_price',
                    ['product' => $product, 'qty' => $bundleQty]
                );
                $price = $product->getData('final_price') * ($selectionProduct->getSelectionPriceValue() / 100);
            } else {
                // fixed
                $price = $selectionProduct->getSelectionPriceValue();
                // add custom
                $this->_eventManager->dispatch(
                    'riki_catalog_product_bundle_get_selection_fixed_price',
                    [
                        'product' => $bundleProduct,
                        'selection_product'   =>  $selectionProduct,
                        'price' => $price
                    ]
                );

                if ($newPrice = $selectionProduct->getNewSelectionPriceValue()) {
                    $price = $newPrice;
                }
            }
        }

        if ($multiplyQty) {
            $price *= $selectionQty;
        }

        return min(
            $price,
            $this->_applyTierPrice($bundleProduct, $bundleQty, $price),
            $this->_applySpecialPrice($bundleProduct, $price)
        );
    }
}
