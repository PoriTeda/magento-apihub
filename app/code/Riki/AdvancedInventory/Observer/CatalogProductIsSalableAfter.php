<?php
/**
 * Riki Advanced Inventory
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\AdvancedInventory\Observer
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\AdvancedInventory\Observer;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Backend\Model\Locale\Resolver;
use Magento\Framework\App\State;
use Wyomind\AdvancedInventory\Observer\CatalogProductIsSalableAfter as OriginalCatalogProductIsSalableAfter;

/**
 * Class CatalogProductIsSalableAfter
 *
 * @category  RIKI
 * @package   Riki\AdvancedInventory\Observer
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class CatalogProductIsSalableAfter extends OriginalCatalogProductIsSalableAfter
{
    const SUBSCRIPTION_REPLACE_SUBMIT = 'subscription_replace_submit';

    /**
     * @var \Riki\AdvancedInventory\Helper\Assignation
     */
    protected $assignationHelper;
    /**
     * @var \Riki\Catalog\Model\StockState
     */
    protected $stockState;

    /*global variable is used to store stock status data*/
    protected $checkedProductsStockStatus = [];

    /*List point of sales will be used to validated stock status*/
    protected $placeIds = [];

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    protected $state;

    public function __construct(
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Wyomind\AdvancedInventory\Model\Stock $modelStock,
        \Wyomind\Core\Helper\Data $coreHelperData,
        \Wyomind\PointOfSale\Model\PointOfSale $modelPointOfSale,
        \Riki\AdvancedInventory\Helper\Assignation $assignationHelper,
        \Riki\Catalog\Model\StockState $stockState,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\App\State $state
    ) {
        parent::__construct($coreHelperData, $modelStock, $storeManager, $stockRegistry, $modelPointOfSale);
        $this->assignationHelper = $assignationHelper;
        $this->stockState = $stockState;
        $this->request = $request;
        $this->productFactory = $productFactory;
        $this->state = $state;
    }

    /**
     * set list point of sales before get product stock status
     */
    public function setPlaceIds()
    {
        $areaCode = $this->state->getAreaCode();
        $this->placeIds = $this->assignationHelper->getDefaultPosForFo();
        if ($areaCode == 'adminhtml') {
            $this->placeIds = $this->assignationHelper->getPointOfSaleHelper()->getPlaces();
        }
    }

    /**
     * @return array
     */
    public function getPlaceIds()
    {
        if (!$this->placeIds) {
            return $this->setPlaceIds();
        }
        return $this->placeIds;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // Get current product from observer.
        $product = $observer->getProduct();

        // If the action is subscription replace submit
        // Not check product stock status for discontinue product.
        if ($this->request->getFullActionName() == self::SUBSCRIPTION_REPLACE_SUBMIT) {
            $discontinueProduct = $this->request->getParam('replace_discontinue_product', false);

            if (!empty($discontinueProduct)) {
                $discontinueProductId = $this->productFactory->create()->getIdBySku($discontinueProduct);

                if ($product->getParentProductId() == $discontinueProductId) {
                    return;
                }
            }
        }

        $storeId = $this->_storeManager->getStore()->getStoreId();

        if (!$product->getIsOosProduct()) {
            if (isset($this->checkedProductsStockStatus[$product->getId()])) {
                if (isset($this->checkedProductsStockStatus[$product->getId()][$storeId])) {
                    $rtn = $this->checkedProductsStockStatus[$product->getId()][$storeId];
                    if ($rtn !== null) {
                        $observer->getSalable()->setIsSalable($rtn);
                        return;
                    }
                }
            } else {
                $this->checkedProductsStockStatus[$product->getId()] = [];
            }
        }

        if ($this->_coreHelperData->getStoreConfig("advancedinventory/settings/enabled")) {
            $product = $observer->getProduct();

            if (in_array($product->getTypeId(), [
                "downloadable",
                "virtual",
                \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
            ])
            ) {
                $observer->getSalable()->setIsSalable(false);
                return;
            }

            $rtn = $this->stockState->isSalable($product, $this->getPlaceIds());

            if ($rtn !== null) {
                $observer->getSalable()->setIsSalable($rtn);
            }
        }
        if (!$product->getIsOosProduct()) {
            $this->checkedProductsStockStatus[$product->getId()][] = $rtn;
        }
    }
}
