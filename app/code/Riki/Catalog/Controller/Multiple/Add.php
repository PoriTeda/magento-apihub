<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Catalog\Controller\Multiple;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\SubscriptionCourse\Helper\Data as SubscriptionCourseHelper;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add extends \Magento\Checkout\Controller\Cart
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subscriptionPageHelper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_resolverInterface;
    /**
     * @var \Magento\Framework\Escaper
     */
    protected $_escaper;
    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $_cartHelper;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_interfaceLog;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;
    /* @var \Magento\Framework\UrlInterface */
    protected $_urlBuilder;
    /**
     * @var SubscriptionCourseHelper
     */
    protected $_subscriptionCourseHelper;
    /* @var
     * \Riki\SubscriptionCourse\Model\Course
     */
    protected $subCourseModel;

    /**
     * @var \Riki\SubscriptionPage\Helper\CheckRequestLineApp
     */
    protected $_helperLineApp;

    /**
     * @var \Riki\Catalog\Model\StockState
     */
    protected $stockState;

    /**
     * @var \Magento\GiftWrapping\Api\WrappingRepositoryInterface
     */
    protected $wrappingRepository;

    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Add constructor.
     * @param SubscriptionCourseHelper                             $subscriptionCourseHelper
     * @param \Magento\Framework\Registry                          $registry
     * @param \Magento\Framework\App\Action\Context                $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface   $scopeConfig
     * @param \Magento\Checkout\Model\Session                      $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator       $formKeyValidator
     * @param CustomerCart                                         $cart
     * @param \Riki\SubscriptionPage\Helper\Data                   $subscriptionPageHelper
     * @param ProductRepositoryInterface                           $productRepository
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     * @param \Magento\Framework\Locale\ResolverInterface          $resolverInterface
     * @param \Magento\Framework\Escaper                           $escaper
     * @param \Magento\Checkout\Helper\Cart                        $cartHelper
     * @param \Psr\Log\LoggerInterface                             $interfaceLog
     * @param \Magento\Framework\Json\Helper\Data                  $jsonHelper
     * @param \Riki\SubscriptionCourse\Model\Course                $subCourseModel
     * @param \Riki\Catalog\Model\StockState                       $stockState
     * @param \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository
     * @param \Magento\GiftWrapping\Helper\Data                    $helperData
     * @param \Psr\Log\LoggerInterface                             $logger
     */
    public function __construct(
        SubscriptionCourseHelper $subscriptionCourseHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper,
        ProductRepositoryInterface $productRepository,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface,
        \Magento\Framework\Locale\ResolverInterface $resolverInterface,
        \Magento\Framework\Escaper $escaper,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Psr\Log\LoggerInterface $interfaceLog,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Riki\SubscriptionCourse\Model\Course $subCourseModel,
        \Riki\SubscriptionPage\Helper\CheckRequestLineApp $helperLineApp,
        \Riki\Catalog\Model\StockState $stockState,
        \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository,
        \Magento\GiftWrapping\Helper\Data $helperData,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->subscriptionPageHelper = $subscriptionPageHelper;
        $this->productRepository = $productRepository;
        $this->_registry = $registry;
        $this->stockRegistry = $stockRegistryInterface;
        $this->storeManager = $storeManager;
        $this->_resolverInterface = $resolverInterface;
        $this->_escaper = $escaper;
        $this->_cartHelper = $cartHelper;
        $this->_interfaceLog = $interfaceLog;
        $this->_jsonHelper = $jsonHelper;
        $this->_urlBuilder = $context->getUrl();
        $this->_subscriptionCourseHelper = $subscriptionCourseHelper;
        $this->subCourseModel = $subCourseModel;
        $this->_helperLineApp = $helperLineApp;
        $this->stockState = $stockState;
        $this->wrappingRepository = $wrappingRepository;
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    /**
     * Initialize product instance from request data
     *
     * @return \Magento\Catalog\Model\Product|false
     */
    protected function _initProductWithId($id)
    {
        $productId = (int)$id;
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

    /**
     * Add product to shopping cart action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        $data = $this->getRequest()->getParams();

        $quote = $this->cart->getQuote();
        //get Course ID
        $courseId = $quote->getData("riki_course_id");
        /**
         * Update rule: if cart had subscription => not allow add product to cart from PDP and Multiple product page.
         */
        if ($courseId) {
            $this->messageManager->addError(__("can't not add product when shopping cart had hanpukai subscription"));
            return $this->goBack($this->getReferUrl($data['riki_multiple_cat']));
        }

        // clear quote
        $quote = $this->_checkoutSession->getQuote();
        foreach($quote->getAllItems() as $quoteItem){
            foreach($data['data']['product'] as $item){
                if($quoteItem->getData('product_id') == $item['product_id']){
                    $quote->deleteItem($quoteItem);
                    break;
                }
            }
        }
        /*
         * End Validate Cart Rule For Subscription
         */
        $data['data']['product'] = $this->mergeItemSameId($data);

        $arrProduct = array();
        try {
            $errorCount = 0 ;
            foreach($data['data']['product'] as $item ) {
                if($item['qty'] > 0) {
                    $erorrProduct = 0 ;
                    $product = $this->_initProductWithId($item['product_id']);
                    /**
                     * Check product availability
                     */
                    if (!$product) {
                        return $this->goBack($this->getReferUrl($data['riki_multiple_cat']), array());
                    }
                    $params = $this->_processItem($item);
                    $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
                    $minimumQty = $stockItem->getMinSaleQty();

                    // Validate qty
                    if ($params['qty'] < $minimumQty) {
                        $erorrProduct = 1 ;
                        $errorCount++;
                        $message = sprintf(
                            __('The purchase quantity of product: %s must be equal or greater than %s'),
                            $product->getName(), $minimumQty);
                        $this->messageManager->addErrorMessage($message);


                    }

                    /*validate stock before add to cart*/
                    if (!$this->stockState->canAssigned($product, $params['qty'], $this->stockState->getPlaceIds())) {
                        $erorrProduct = 1 ;
                        $errorCount++;
                        $message = sprintf(
                            __("We don't have as many \"%s\" as you requested."),
                            $product->getName()
                        );

                        $this->messageManager->addErrorMessage($message);
                    }
                    // Validate Course in cart
                    if ($courseId) {
                        $arrProductId = [$item['product_id']];
                        foreach ($quote->getAllVisibleItems() as $item) {
                            if ($item->getData('is_riki_machine') == 1) {
                                continue;
                            }
                            $arrProductId[] = $item->getProductId();
                        }

                        // Check product must belong course
                        $errorCode = $this->_subscriptionCourseHelper->checkCartIsValidForCourse($arrProductId, $courseId);

                        // have spot hanpukai
                        if ($this->_subscriptionCourseHelper->getSubscriptionCourseType($courseId) == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
                            $erorrProduct = 1 ;
                            $errorCount++;
                            $errorCode = 2;
                        }
                        // Type error
                        switch ($errorCode) {
                            case 1:
                                $erorrProduct = 1 ;
                                $errorCount++;
                                $this->messageManager->addError(__("shopping cart cannot contain SPOT and subscription at the same time"));
                                break;
                            case 2:
                                $this->messageManager->addError(__("can't not add product when shopping cart had hanpukai subscription"));
                                break;
                            default:
                                // Do nothing
                        }
                    }
                    if($erorrProduct == 0 ){
                            $this->cart->addProduct($product, $params,$this->getReferUrl($data['riki_multiple_cat']));
                            if ($product) {
                                $arrProduct[] = $product;
                            }
                        }


                }
            }
            if($errorCount == 0){
                $this->cart->save();
                $listGiftWrapping = $this->getListGiftWrapping($this->getRequest()->getParams());

                if (count($listGiftWrapping) > 0) {
                    foreach ($quote->getAllVisibleItems() as $item) {
                        if (isset($listGiftWrapping[$item->getProductId()])) {
                            $this->updateWrappingItem($listGiftWrapping[$item->getProductId()], $item);
                        }
                    }
                }

            }else{
                return $this->goBack($this->getReferUrl($data['riki_multiple_cat']));
            }

            if (!empty($arrProduct)) {
                $this->_processMessage($arrProduct);
            }
            return $this->goBack(null, $arrProduct);

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNotice(
                    $this->_escaper->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addError(
                        $this->_escaper->escapeHtml($message)
                    );
                }
            }
            $url = $this->_checkoutSession->getRedirectUrl(true);
            if (!$url) {
                $cartUrl = $this->_cartHelper->getCartUrl();
                $url = $this->_redirect->getRedirectUrl($cartUrl);
            }
            return $this->goBack($this->getReferUrl($data['riki_multiple_cat']));

        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
            $this->_interfaceLog->critical($e);
            return $this->goBack($this->getReferUrl($data['riki_multiple_cat']));
        }
    }

    /**
     * Resolve response
     *
     * @param string $backUrl
     * @param \Magento\Catalog\Model\Product $product
     * @return $this|\Magento\Framework\Controller\Result\Redirect
     */
    protected function goBack($backUrl = null, $arrProduct = null)
    {
        if (!$this->getRequest()->isAjax()) {
            $backUrl =  parent::_goBack($backUrl);
            $backUrl->setUrl($this->_helperLineApp->checkRequestAddParam($this->getBackUrl()));
            return $backUrl;
        }

        $result = [];

        if ($backUrl || $backUrl = $this->getBackUrl()) {
            $result['backUrl'] = $this->_helperLineApp->checkRequestAddParam($backUrl);
        } else {
            if (is_array($arrProduct)) {
                foreach ($arrProduct as $product) {
                    if ($product && !$product->getIsSalable()) {
                        $result['product'] = [
                            'statusText' => __('Out of stock')
                        ];
                    }
                }
            }
        }

        $this->getResponse()->representJson(
            $this->_jsonHelper->jsonEncode($result)
        );
    }

    /**
     * @param $arrConfigurable
     * @return array
     */
    public function makeArrConfigurableAttribute($arrConfigurable)
    {
        $result = array();
        foreach ($arrConfigurable as $attributeId => $attributeName)
        {
            $result[$attributeId] = $attributeName;
        }
        return $result;
    }




    public function mergeItemSameId($data)
    {
        $arrResult = [];
        $arrDataProduct = $data['data']['product'];
        foreach ($arrDataProduct as $item)
        {
            if ($this->checkProductExitsInArr($arrResult, $item['product_id'])) {
                $arrResult[$item['product_id']]['qty'] += $item['qty'];
            } else {
                $arrResult[$item['product_id']] = $item;
            }
        }
        return $arrResult;
    }

    public function checkProductExitsInArr($arrResult, $productId)
    {
        $arrAllKey = array_keys($arrResult);
        if (in_array($productId, $arrAllKey)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Process params for add to cart
     * @param $item
     * @return array
     */
    protected function _processItem($item){
        $params = array();
        $params['qty'] = $item['qty'];
        if (isset($params['qty'])) {
            $filter = new \Zend_Filter_LocalizedToNormalized(
                ['locale' => $this->_resolverInterface->getLocale()]
            );
            $params['qty'] = $filter->filter($params['qty']);
        }
        if (isset($item['product_type']) && $item['product_type'] == 'configurable') {
            $params['super_attribute'] = $this->makeArrConfigurableAttribute($item['super_attribute']);
        }

        if(isset($item['product_type']) && $item['product_type'] == 'bundle') {
            $bundleOption = $item['bundle_option'];
            $bundleOptionQty = array();
            if (isset($item['bundle_option_qty']) && is_array($item['bundle_option_qty'])) {
                foreach ($item['bundle_option_qty'] as $key => $value) {
                    $bundleOptionQty[$key] = $value;
                }
            }
            $params['bundle_option'] = $bundleOption;
            $params['bundle_option_qty'] = $bundleOptionQty;
        }
        return $params;
    }
    protected function _processMessage($arrProduct){
        if (!empty($arrProduct)) {
            foreach ($arrProduct as $product) {
                /**
                 * @todo remove wishlist observer \Magento\Wishlist\Observer\AddToCart
                 */
                $this->_eventManager->dispatch(
                    'checkout_cart_add_product_complete',
                    ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
                );

                if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                    if (!$this->cart->getQuote()->getHasError()) {
                        $message = __(
                            'You added %1 to your shopping cart.',
                            $product->getName()
                        );
                    }
                }
            }
        }
    }
    /**
     * @param $categoryId
     *
     * @return string
     */
    public function getReferUrl($catId)
    {
        return $this->_urlBuilder->getUrl('catalog/multiple/view', ['id' => $catId]);
    }

    /**
     * @param $wrappingId
     * @param $item
     *
     * @return bool
     */
    private function updateWrappingItem($wrappingId, $item)
    {
        if ($this->helperData->isGiftWrappingAvailableForItems()) {

            try {
                if ($wrappingId == -1) {
                    $gwId = '';
                    $gCode = '';
                    $sapCode = '';
                    $gw = '';
                } else {
                    $wrapping = $this->wrappingRepository->get($wrappingId);
                    if (!$wrapping) {
                        return false;
                    }
                    $gwId = $wrapping->getId();
                    $gCode = $wrapping->getGiftCode();
                    $sapCode = $wrapping->getSapCode();
                    $gw = $wrapping->getGiftName();
                }

                $item->setGwId($gwId)
                    ->setGiftCode($gCode)
                    ->setSapCode($sapCode)
                    ->setGiftWrapping($gw);

                if (empty($gwId)) {
                    $item->setGwPrice(0)
                        ->setGwBasePrice(0)
                        ->setGwBaseTaxAmount(0)
                        ->setGwTaxAmount(0);
                }

                $item->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);

                return false;
            }
        }
    }

    /**
     * @param $productList
     * @return array
     */
    private function getListGiftWrapping($productList) {
        $listGiftWrapping = [];
        foreach($productList['data']['product'] as $item ) {
            if ( $item['gift_wrapping'] > 0) {
                $listGiftWrapping[$item['product_id']] = $item['gift_wrapping'];
            }
        }
        return $listGiftWrapping;
    }

}
