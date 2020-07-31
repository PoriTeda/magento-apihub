<?php

namespace Riki\Checkout\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class Cart extends \Magento\Checkout\Model\Cart
{
    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $subCourseModelFactory;
    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $subCourseHelperData;
    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $orderAmountRestriction;

    /**
     * @var bool
     */
    protected $validateMaxMinCourse = false;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\ResourceModel\Cart $resourceCart,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        ProductRepositoryInterface $productRepository,
        \Riki\SubscriptionPage\Helper\Data $subHelper,
        \Magento\Framework\App\Request\Http $request,
        \Riki\SubscriptionCourse\Helper\Data $subCourseHelper,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepositoryInterface,
        \Riki\Subscription\Helper\Order $orderAmountRestriction,
        array $data = []
    ) {
        parent::__construct(
            $eventManager,
            $scopeConfig,
            $storeManager,
            $resourceCart,
            $checkoutSession,
            $customerSession,
            $messageManager,
            $stockRegistry,
            $stockState,
            $quoteRepository,
            $productRepository,
            $data
        );
        $this->_registry = $registry;
        $this->request = $request;
        $this->subHelper = $subHelper;
        $this->subCourseHelperData = $subCourseHelper;
        $this->subCourseModelFactory = $courseFactory;
        $this->categoryRepository  = $categoryRepositoryInterface;
        $this->orderAmountRestriction = $orderAmountRestriction;
    }

    /**
     * Override Update Item
     */
    public function updateItems($data)
    {
        $infoDataObject = new \Magento\Framework\DataObject($data);
        $infoUpdate = [];
        $this->_eventManager->dispatch(
            'checkout_cart_update_items_before',
            ['cart' => $this, 'info' => $infoDataObject]
        );

        $qtyRecalculatedFlag = false;
        foreach ($data as $itemId => $itemInfo) {
            $item = $this->getQuote()->getItemById($itemId);
            if (!$item) {
                continue;
            }

            if (!empty($itemInfo['remove']) || isset($itemInfo['qty']) && $itemInfo['qty'] == '0') {
                $this->removeItem($itemId);
                continue;
            }

            $qty = isset($itemInfo['qty']) ? (double)$itemInfo['qty'] : false;
            if ($qty > 0) {
                // multi_shipping_qty is a qty of parent item when split item during multiple checkout
                // it used for prevent quantity validator min_qty on child item
                if ($item->hasData('multi_shipping_qty')) {
                    $qtyMultiShipping = intval($item->getData('multi_shipping_qty'));
                    $item->setData('multi_shipping_qty', $qtyMultiShipping - $item->getQty() + $qty);
                }
                $beforeQty = $item->getQty();
                $infoUpdate[$item->getId()] = $beforeQty;
                $item->setQty($qty);

                if ($item->getHasError()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($item->getMessage()));
                }

                if (isset($itemInfo['before_suggest_qty']) && $itemInfo['before_suggest_qty'] != $qty) {
                    $qtyRecalculatedFlag = true;
                    $this->messageManager->addNotice(
                        __('Quantity was recalculated from %1 to %2', $itemInfo['before_suggest_qty'], $qty),
                        'quote_item' . $item->getId()
                    );
                }
            }
        }

        if ($qtyRecalculatedFlag) {
            $this->messageManager->addNotice(
                __('We adjusted product quantities to fit the required increments.')
            );
        }
        $this->_eventManager->dispatch(
            'checkout_cart_update_items_after',
            ['cart' => $this, 'info' => $infoDataObject, 'info_update' =>$infoUpdate]
        );

        return $this;
    }

    public function updateItem($itemId, $requestInfo = null, $updatingParams = null)
    {
        if ($this->isHanpukaiSubscription()) {
            $result = "Can't not update product qty for hanpukai subscription";
            throw new \Magento\Framework\Exception\LocalizedException(__($result));
        }
        return parent::updateItem($itemId, $requestInfo, $updatingParams);
    }

    public function removeItem($itemId)
    {
        if ($this->isHanpukaiSubscription()) {
            if (!$this->_registry->registry('ssoid_clear_cart')) {
                $this->messageManager->addNotice(__('Hanpukai had been removed from shopping cart'));
            }
            return $this->truncate();
        }

        //validate for rule subscription
        $iCourseId = $this->_checkoutSession->getQuote()->getRikiCourseId();
        if($iCourseId && strpos($this->request->getPathInfo(),'/checkout/sidebar/removeItem') !== false) {

            $objCourse = $this->subCourseModelFactory->create()->load($iCourseId);
            $result = $this->validateSubscriptionRuleRemoveItem($itemId, $this->_checkoutSession->getQuote());

            $categoryName = '';
            $qtyOfCategory = 0;
            $arrCategoryIdQtyConfig = [];

            $mustHaveCatId = $objCourse->getData("must_select_sku");
            if($mustHaveCatId){
                $arrCategoryIdQtyConfig =  explode(':', $mustHaveCatId);
            }
            if (count($arrCategoryIdQtyConfig) > 1) {
                $categoryId = $arrCategoryIdQtyConfig[0];
                if ($categoryObj = $this->categoryRepository->get($categoryId)) {
                    $categoryName = $categoryObj->getName();
                } else {
                    $categoryName = '';
                }
                $qtyOfCategory = $arrCategoryIdQtyConfig[1];
            }

            switch ($result) {
                case 4:
                    $messageError = __("In %1, the total number of items in the shopping cart have at least %2 quantity",$objCourse->getData('course_name'),$objCourse->getData('minimum_order_qty'));
                    throw new LocalizedException($messageError);
                case 5:
                    $messageError = __("You need to purchase items of %1",$categoryName);
                    throw new LocalizedException($messageError);
                default:
                    // Do nothing
            }
        }
        return parent::removeItem($itemId);
    }

    /**
     * Validate subscription rule when edit product
     *
     * @param $itemIdUpdate
     * @param $quote
     * @return int
     */
    public function validateSubscriptionRuleRemoveItem($itemIdUpdate, $quote)
    {
        /* @var $quote \Magento\Quote\Model\Quote */
        $objCourse = $this->subCourseModelFactory->create()->load($quote->getRikiCourseId());
        $arrProductId = [];
        $arrProductIdQty = [];
        $totalQtyShow = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $buyRequest = $item->getBuyRequest();
            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                continue;
            }
            if ($item->getData('is_riki_machine')) {
                continue;
            }

            if ($item->getId() == $itemIdUpdate && $itemIdUpdate == 0) {
                continue;
            }

            if ($item->getId() != $itemIdUpdate) {
                if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                    $unitQty = ((int)$item->getUnitQty() != 0)?(int)$item->getUnitQty():1;
                    $arrProductIdQty[$item->getProduct()->getId()] = $item->getQty()/$unitQty;
                }
                else{
                    $arrProductIdQty[$item->getProduct()->getId()] = $item->getQty();
                }
            }
            else{
                $arrProductIdQty[$item->getProduct()->getId()] = 0;
            }

            $arrProductId[] = $item->getProduct()->getId();
            if ($item->getData('is_addition')) {
                continue;
            }
            if ($item->getId() != $itemIdUpdate) {
                if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE) {
                    $totalQtyShow = $totalQtyShow + $item->getQty();
                }

                if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                    $totalQtyShow = $totalQtyShow + ($item->getQty() / $item->getUnitQty());
                }
            }
        }

        $mustHaveCatId = $objCourse->getData('must_select_sku');
        $isValid = $this->subCourseHelperData->isValidMakeHaveInCart($arrProductId, $mustHaveCatId);
        if (!$isValid) {
            return 3;
        }

        $minimumOrderQty = $objCourse->getData('minimum_order_qty');
        if( $totalQtyShow < $minimumOrderQty ) {
            return 4; // Maximum limit
        }

        if (!$this->subCourseHelperData->isValidMustHaveQtyInCategory($arrProductIdQty, $mustHaveCatId)) {
            return 5; // Minimum product qty in category
        }

        return 0;
    }

    public function isHanpukaiSubscription()
    {
        $courseId = $this->_checkoutSession->getQuote()->getData('riki_course_id');
        if (!empty($courseId)) {
            if ($this->subHelper->getSubscriptionType($courseId) == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int|\Magento\Catalog\Model\Product $productInfo
     * @param null $requestInfo
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addProduct($productInfo, $requestInfo = null)
    {
        $product = $this->_getProduct($productInfo);
        $request = $this->_getProductRequest($requestInfo);
        $productId = $product->getId();

        if ($productId) {
            try {
                $result = $this->getQuote()->addProduct($product, $request);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_checkoutSession->setUseNotice(false);
                $result = $e->getMessage();
            }
            /**
             * String we can get if prepare process has error
             */
            if (is_string($result)) {
                if ($product->hasOptionsValidationFail()) {
                    $redirectUrl = $product->getUrlModel()->getUrl(
                        $product,
                        ['_query' => ['startcustomization' => 1]]
                    );
                } else {
                    $redirectUrl = $product->getProductUrl();
                }
                $this->_checkoutSession->setRedirectUrl($redirectUrl);
                if ($this->_checkoutSession->getUseNotice() === null) {
                    $this->_checkoutSession->setUseNotice(true);
                }
                throw new \Magento\Framework\Exception\LocalizedException(__($result));
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('The product does not exist.'));
        }

        $this->_eventManager->dispatch(
            'checkout_cart_product_add_after',
            ['quote_item' => $result, 'product' => $product]
        );
        $this->_checkoutSession->setLastAddedProductId($productId);
        return $this;
    }

    public function setValidateMaxMinCourse($value)
    {
         $this->validateMaxMinCourse = $value;
    }

    /**
     * Save cart
     *
     * @return $this
     * @throws LocalizedException
     */
    public function save()
    {
        $this->_eventManager->dispatch('checkout_cart_save_before', ['cart' => $this]);

        $this->getQuote()->getBillingAddress();
        $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        $this->getQuote()->collectTotals();

        /**
         * Validate max min for subscription config course
         */
        if ($this->validateMaxMinCourse) {
            $quote = $this->getQuote();
            $subCourseId = $quote->getData('riki_course_id');
            if ($subCourseId > 0) {
                $result = $this->orderAmountRestriction->validateAmountFirstOrderSimulator($quote);
                if (isset($result['status']) && !$result['status']) {
                    throw new \Magento\Framework\Exception\LocalizedException($result['message']);
                }
            }
        }

        $this->quoteRepository->save($this->getQuote());
        $this->_checkoutSession->setQuoteId($this->getQuote()->getId());
        /**
         * Cart save usually called after changes with cart items.
         */
        $this->_eventManager->dispatch('checkout_cart_save_after', ['cart' => $this]);
        $this->reinitializeState();
        return $this;
    }
}
