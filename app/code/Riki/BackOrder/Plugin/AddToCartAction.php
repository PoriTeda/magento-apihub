<?php

namespace Riki\BackOrder\Plugin;

use Riki\BackOrder\Helper\Data as BackOrderHelper;
use Riki\AdvancedInventory\Model\Stock as AdvancedInventoryStock;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;

class AddToCartAction
{
    /**
     * @var \Wyomind\AdvancedInventory\Model\StockRepositery
     */
    protected $stockRepository;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $cartHelper;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $redirectFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var BackOrderHelper
     */
    protected $backOrderHelper;

    /**
     * @var \Riki\Catalog\Helper\Data
     */
    protected $catalogHelper;

    /**
     * AddToCartAction constructor.
     *
     * @param \Wyomind\AdvancedInventory\Model\StockRepositery $stockRepository
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Checkout\Helper\Cart $cartHelper
     * @param \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Riki\Catalog\Helper\Data $catalogHelper,
        \Riki\BackOrder\Helper\Data $backOrderHelper,
        \Wyomind\AdvancedInventory\Model\StockRepositery $stockRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riki\AdvancedInventory\Helper\Inventory $helperInventory
    ) {
        $this->catalogHelper = $catalogHelper;
        $this->backOrderHelper = $backOrderHelper;
        $this->stockRepository = $stockRepository;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        $this->jsonHelper = $jsonHelper;
        $this->cartHelper = $cartHelper;
        $this->redirectFactory = $redirectFactory;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->checkoutSession = $checkoutSession;
        $this->helperInventory = $helperInventory;
    }

    /**
     * Initialize product instance from request data
     *
     * @return \Magento\Catalog\Model\Product|false
     */
    protected function _initProduct($productId)
    {
        if ($productId) {
            $storeId = $this->storeManager->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Around execute
     *
     * @param \Magento\Checkout\Controller\Cart\Add $subject
     * @param \Closure $proceed
     * @return bool|\Closure|mixed
     */
    public function aroundExecute(
        \Magento\Checkout\Controller\Cart\Add $subject,
        \Closure $proceed
    ) {
        // Get params
        $storeId = $this->storeManager->getStore()->getId();
        $getParams = $subject->getRequest()->getParams();
        $productId = (int)$getParams['product'];
        $qtyProduct = isset($getParams['qty']) ? (int)$getParams['qty'] : null;
        $product = $this->_initProduct($productId);
        if (!$product) {
            return false;
        }

        if (isset($getParams['productpage'])
            && $getParams['productpage'] == CaseDisplay::PRODUCT_LISTING_PAGE
            && $product->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY
        ) {
            $unitQty = $product->getUnitQty() ? $product->getUnitQty() : 1;
            $qtyProduct = $qtyProduct * $unitQty;
        }

        $arrProduct = $this->getAllProductInQuote();
        if (array_key_exists($product->getId(), $arrProduct)) {
            $arrProduct[$product->getId()]['qty'] += $qtyProduct;
        } else {
            $arrProduct[$product->getId()]['product'] = $product;
            $arrProduct[$product->getId()]['qty'] = $qtyProduct;
            $arrProduct[$product->getId()]['assignation'] = '';
        }

        $totalQtyInFo = 0;
        foreach ($arrProduct as $productId => $productData) {
            list($unitQty,$caseDisplay) = $this->catalogHelper->getProductUnitInfo($productId);
            if (strtoupper($caseDisplay)
                == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                $totalQtyInFo = $totalQtyInFo + $productData['qty'] / $unitQty;
            } else {
                $totalQtyInFo = $totalQtyInFo + $productData['qty'];
            }
        }

        $totalQtyInFo = $totalQtyInFo + $this->totalQtyOfFreeProduct();
        /**
         * Check Maximum Order Qty
         */
        $maximumOrderQtyConfig = $this->getConfig(AdvancedInventoryStock::ADVANCED_INVENTORY_MAXIMUM_CART_STOCK);
        if ($maximumOrderQtyConfig > 0 && $maximumOrderQtyConfig < $totalQtyInFo) {
            $msg = 'I am sorry. <br> In the Nestle Online shopping online shop, ';
            $msg .= 'we have restricted the maximum number of items as %s at one order. ';
            $msg .= 'Sorry to trouble you, but please change the number of items in the cart to %s pieces or less.';
            $message = sprintf(__($msg), $maximumOrderQtyConfig, $maximumOrderQtyConfig);
            $this->messageManager->addError($message);
            return $subject->addToCartResponse($subject->getRedirectUrl(), $product);
        }

        return $proceed();
    }

    /**
     * Get all product in quote
     *
     * @return array
     */
    public function getAllProductInQuote()
    {
        $arrResult = [];
        $quote = $this->checkoutSession->getQuote();
        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return $arrResult;
        }
        /** @var $quote \Magento\Quote\Model\Quote */
        $items = $quote->getAllItems();
        if (!empty($items)) {
            foreach ($items as $item) {
                $buyRequest = $item->getBuyRequest();
                if (isset($buyRequest['options']['ampromo_rule_id'])) {
                    continue;
                }
                if ($item->getData('is_riki_machine')) {
                    continue;
                }
                $product = $item->getProduct();
                $arrResult[$product->getId()]['product'] = $product;
                $arrResult[$product->getId()]['qty'] = $item->getQty();
                $arrResult[$product->getId()]['assignation'] = '';
            }
        }
        return $arrResult;
    }

    /**
     * Get Store Config
     *
     * @param $path
     *
     * @return string
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Total qty of all free product (free gift, free machine)
     *
     * @return int
     */
    public function totalQtyOfFreeProduct()
    {
        /** @var $quote \Magento\Quote\Model\Quote */
        $quote = $this->checkoutSession->getQuote();
        $total = 0;
        $items = $quote->getAllItems();
        if (!empty($items)) {
            foreach ($items as $item) {
                $buyRequest = $item->getBuyRequest();
                if (isset($buyRequest['options']['ampromo_rule_id']) || $item->getData('is_riki_machine')) {
                    $qtyShowInFo = $this->getQtyShowInFo($item);
                    $total = $total + $qtyShowInFo;
                }
            }
        }
        return $total;
    }

    /**
     * Get Qty Show In Fo
     *
     * @param $item
     *
     * @return int
     */
    public function getQtyShowInFo($item)
    {
        if (strtoupper($item->getUnitCase())
            == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
            return $item->getQty() / $item->getUnitQty();
        } else {
            return $item->getQty();
        }
    }
}
