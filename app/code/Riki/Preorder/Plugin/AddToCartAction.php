<?php

namespace Riki\Preorder\Plugin;

use Magento\Framework\Exception\NoSuchEntityException;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;

class AddToCartAction
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $redirectFactory;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cartModel;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $cartSession;
    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $cartHelper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager;
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    protected $jsonHelper;
    /**
     * @var \Riki\AdvancedInventory\Helper\Inventory
     */
    protected $helperInventory;
    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $helper;
    /**
     * @var \Wyomind\AdvancedInventory\Model\Stock
     */
    protected $stock;
    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $helperOrder;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Checkout\Model\Cart $cartModel,
        \Magento\Checkout\Model\Session $cartSession,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Riki\AdvancedInventory\Helper\Inventory $helperInventory,
        \Riki\Preorder\Helper\Data $helper,
        \Wyomind\AdvancedInventory\Model\Stock $stock,
        \Riki\Sales\Helper\Order $helperOrder
    ) {
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        $this->redirectFactory = $redirectFactory;
        $this->productRepository = $productRepository;
        $this->cartModel = $cartModel;
        $this->cartSession = $cartSession;
        $this->cartHelper = $cartHelper;
        $this->storeManager = $storeManager;
        $this->jsonHelper = $jsonHelper;
        $this->helperInventory = $helperInventory;
        $this->helper = $helper;
        $this->stock = $stock;
        $this->helperOrder = $helperOrder;
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
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    public function aroundExecute(\Magento\Checkout\Controller\Cart\Add $subject, \Closure $proceed)
    {
        $params = $subject->getRequest()->getParams();

        $productId = (int)$params['product'];
        
        $product = $this->_initProduct($productId);

        if (!$product) {
            return false;
        }

        if (isset($params['qty'])
            && isset($params['productpage'])
            && $params['productpage'] == CaseDisplay::PRODUCT_LISTING_PAGE
            && $product->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY
        ) {
            $unitQty = $product->getUnitQty() ? $product->getUnitQty() : 1;
            $params['qty'] = $params['qty'] * $unitQty;
        }

        //check bundle warehouse by rule of riki
        if (isset($params['qty']) && $product->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
            $qtyOrdered = $this->getQtyItem($product->getId()) + $params['qty'];

            $bundleWarehouse = $this->helperInventory->checkWarehouseBundle($product, $qtyOrdered, 1);

            if ($bundleWarehouse['error']) {
                return $proceed();
            } else {
                // Message error for sub items bundle miniQty
                if ($bundleWarehouse['error_type'] == 'sub_bundle') {
                    $message = __(
                        'Please select QTY to be more than %1 for %2',
                        $bundleWarehouse['mini_qty'] * 1,
                        $product->getName()
                    );
                } else {
                    $message = __('We don\'t have as many "%1" as you requested.', $product->getName());
                }

                $this->messageManager->addErrorMessage($message);
                return $subject->addToCartResponse($subject->getRedirectUrl(), $product);
            }
        }

        /*product but cannot add to cart ( launch date is future date)*/
        $launchFrom = $product->getLaunchFrom();

        if (!empty($launchFrom)) {
            $originDate =  $this->timezone->formatDateTime($this->dateTime->gmtDate(), 2, 2);
            $todayDate  = $this->dateTime->gmtDate('Y-m-d', $originDate);

            if (strtotime($launchFrom) > strtotime($todayDate)) {
                $msg = __(
                    'You can only buy a particular product at %1.',
                    $this->timezone->date($launchFrom)->format('d-m-Y')
                );
                $this->messageManager->addErrorMessage($msg);
                return $subject->addToCartResponse($subject->getRedirectUrl(), $product);
            }
        }

        /*check product is pre order product*/
        $isPreorderProduct = $this->helper->getIsProductPreorder($product);

        //validate only buy a pre-order product not contain any other product in cart
        $validate = true;
        $quote = $this->cartModel->getQuote();

        foreach ($quote->getAllVisibleItems() as $item) {
            if ($isPreorderProduct) {
                if ($item->getProductId() != $productId) {
                    $validate = false;
                }
            } else {
                if ($this->helper->getIsProductPreorder($this->_initProduct($item->getProductId()))) {
                    $validate = false;
                    break;
                }
            }
        }

        if (!$validate) {
            $this->messageManager->addErrorMessage($this->helper->cartMultiTypeProductMessage());
            return $subject->addToCartResponse($subject->getRedirectUrl(), $product);
        } else {
            return $proceed();
        }
    }

    /**
     * @param $productId
     * @return bool
     */
    public function getQtyItem($productId)
    {
        $quote = $this->cartSession->getQuote();
        foreach ($quote->getAllItems() as $item) {
            if ($item->getProductId() == $productId) {
                return $item->getQty();
            }
        }
        return false;
    }
}
