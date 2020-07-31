<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\SubscriptionPage\Controller\Ajax;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\StateException;
use Riki\SubscriptionPage\Model\Cart as CustomerCart;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Riki\Subscription\Model\Constant;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Magento\Framework\Locale\ResolverInterface as ResolverInterface;
use Magento\Framework\Escaper as Escaper;
use Magento\Checkout\Helper\Cart as CartHelper;
use Psr\Log\LoggerInterface as LoggerInterface;
use Magento\Framework\Json\Helper\Data as JsonHelperData;
use Zend\Serializer\Adapter\Json;
use Riki\SubscriptionCourse\Helper\Data as SubscriptionCourseHelper;
use Magento\Framework\UrlInterface;
use Riki\BackOrder\Helper\Data as BackOrderHelperData;
use Riki\AdvancedInventory\Model\Stock as AdvancedInventoryStock;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add extends \Magento\Checkout\Controller\Cart
{
    const TOTAL_AMOUNT_OPTION_ALL_ORDER = 1;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    protected $subscriptionPageHelper;

    protected $_registry;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;
    protected $storeManagerInterface;
    protected $resolverInterface;
    protected $escaper;
    protected $cartHelper;
    protected $loggerInterface;
    protected $jsonHelperData;
    protected $subscriptionCourseHelper;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_courseModel;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Riki\Customer\Model\SsoConfig
     */
    protected $ssoConfig;

    /**
     * @var \Riki\Customer\Helper\SsoUrl
     */
    protected $ssoUrl;

    /**
     * @var \Riki\Subscription\Model\Profile\Profile
     */
    protected $profileModel;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Riki\SubscriptionPage\Helper\CheckRequestLineApp
     */
    protected $_helperLineApp;

    /**
     * @var \Riki\SubscriptionCourse\Helper\ValidateDelayPayment
     */
    protected $helperDelayPayment;

    /**
     * @var \Riki\Catalog\Model\StockState
     */
    protected $stockState;

    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $courseRepository;

    protected $helperMachine;

    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $deliveryTypeHelper;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

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
     * @param BackOrderHelperData $backOrderHelperData
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepositoryInterface
     * @param \Riki\Subscription\Model\Profile\Profile $profileModel
     * @param \Riki\Customer\Helper\SsoUrl $ssoUrl
     * @param \Riki\Customer\Model\SsoConfig $ssoConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param SubscriptionCourseHelper $subscriptionCourseHelper
     * @param JsonHelperData $jsonHelperData
     * @param LoggerInterface $loggerInterface
     * @param CartHelper $cartHelper
     * @param Escaper $escaper
     * @param ResolverInterface $resolverInterface
     * @param StoreManagerInterface $storeManagerInterface
     * @param Registry $registry
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     * @param \Riki\SubscriptionCourse\Model\Course $courseModel
     * @param \Riki\SubscriptionPage\Helper\CheckRequestLineApp $helperLineApp
     * @param \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment
     * @param \Riki\Catalog\Model\StockState $stockState
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     * @param \Riki\MachineApi\Helper\Machine $helperMachine
     * @param \Riki\DeliveryType\Helper\Data $deliveryTypeHelper
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     * @param \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository
     * @param \Magento\GiftWrapping\Helper\Data $helperData
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Riki\BackOrder\Helper\Data $backOrderHelperData,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepositoryInterface,
        \Riki\Subscription\Model\Profile\Profile $profileModel,
        \Riki\Customer\Helper\SsoUrl $ssoUrl,
        \Riki\Customer\Model\SsoConfig $ssoConfig,
        \Magento\Customer\Model\Session $customerSession,
        SubscriptionCourseHelper $subscriptionCourseHelper,
        JsonHelperData $jsonHelperData,
        LoggerInterface $loggerInterface,
        CartHelper $cartHelper,
        Escaper $escaper,
        ResolverInterface $resolverInterface,
        StoreManagerInterface $storeManagerInterface,
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
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Riki\SubscriptionPage\Helper\CheckRequestLineApp $helperLineApp,
        \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment,
        \Riki\Catalog\Model\StockState $stockState,
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository,
        \Riki\MachineApi\Helper\Machine $helperMachine,
        \Riki\DeliveryType\Helper\Data $deliveryTypeHelper,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository,
        \Magento\GiftWrapping\Helper\Data $helperData,
        \Psr\Log\LoggerInterface $logger
    )
    {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );

        $this->categoryRepository = $categoryRepositoryInterface;
        $this->profileModel = $profileModel;
        $this->ssoConfig = $ssoConfig;
        $this->_customerSession = $customerSession;
        $this->_urlBuilder = $context->getUrl();
        $this->subscriptionCourseHelper = $subscriptionCourseHelper;
        $this->jsonHelperData = $jsonHelperData;
        $this->loggerInterface = $loggerInterface;
        $this->cartHelper = $cartHelper;
        $this->escaper = $escaper;
        $this->resolverInterface = $resolverInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->subscriptionPageHelper = $subscriptionPageHelper;
        $this->productRepository = $productRepository;
        $this->_registry = $registry;
        $this->stockRegistry = $stockRegistryInterface;
        $this->_courseModel = $courseModel;
        $this->ssoUrl = $ssoUrl;
        $this->_helperLineApp = $helperLineApp;
        $this->helperDelayPayment = $helperDelayPayment;
        $this->stockState = $stockState;
        $this->courseRepository = $courseRepository;
        $this->helperMachine = $helperMachine;
        $this->deliveryTypeHelper = $deliveryTypeHelper;
        $this->subscriptionValidator = $subscriptionValidator;
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
            $storeId = $this->storeManagerInterface->getStore()->getId();
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
        // machine not required
        $this->_checkoutSession->unsMachineNotRequired(); // case of redirect back from checkout
        $multiMachineNotRequired = isset($data['skip_machine']) ? $data['skip_machine'] : false;
        if($multiMachineNotRequired){
            $this->_checkoutSession->setMachineNotRequired(true);
        }
        // clear quote
        $quote = $this->_checkoutSession->getQuote();
        foreach($quote->getAllItems() as $quoteItem){
            if (!$quote->getData('riki_course_id')){
                $message = __('Only one subscription allowed in the shopping cart');
                $this->messageManager->addErrorMessage($message);
                return $this->goBack($data['current_url'], array());
            }
            //case hanpukai
            if (isset($data['subscription_type']) && $data['subscription_type'] != \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI)
            {
                foreach($data['data']['product'] as $item){
                    if($quoteItem->getData('product_id') == $item['product_id'] && $quoteItem->getData('is_addition') != 1){
                        $quote->deleteItem($quoteItem);
                        break;
                    }
                }
            }
        }
        $quote->setData('riki_hanpukai_qty', 0);
        /**
         * Validate free machine out of stock
         */
        if (isset($data['machine_not_available']) && $data['machine_not_available']) {
            $this->messageManager->addErrorMessage(__('I\'m sorry, the machine is out of stock at the moment. Please contact us for confirmation of re-arrival.'));
            return $this->goBack($this->getReferUrl($data['riki_course_id']));
        }

        if (isset($data['subscription_type']) &&
            $data['subscription_type'] == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
            $quote = $this->cart->getQuote();
            if (!isset($data['machines']) && !$this->helperMachine->hasMachineInCart($quote)) {

                if (!$multiMachineNotRequired) {
                    $this->messageManager->addErrorMessage(__('Select at least one machine.'));
                    return $this->goBack($this->getReferUrl($data['riki_course_id']));
                }
            } elseif (!empty($data['machines'])) {
                /** clear cart - only for subscription course multiple machine */
                $allItems = $this->cart->getItems();
                foreach ($allItems as $item) {
                    $buyRequest = $item->getBuyRequest();
                    if ($item->getData('is_riki_machine') == 1 &&
                        isset($buyRequest['is_multiple_machine']) &&
                        $buyRequest['is_multiple_machine'] == 1
                    ) {
                        $this->cart->removeItem($item->getId());
                    }
                }
            }
        }
        /*
         * Validate Cart Rule For Subscription
         */
        $isError = $this->validateAddToCart($data, $multiMachineNotRequired);
        if ($isError == 1) {
            return $this->goBack($data['current_url'], array());
        }

        /*
         * End Validate Cart Rule For Subscription
         */
        $data['data']['product'] = $this->mergeItemSameId($data);
        $arrProduct = [];
        $arrProductIds = [];
        $quote = $this->_checkoutSession->getQuote();
        $quote->setData(\Riki\DeliveryType\Model\Delitype::DELIVERY_TYPE_FLAG, true);
        try {
            foreach ($data['data']['product'] as $item) {
                if ($item['qty'] > 0) {
                    $params = [];
                    $params['qty'] = $item['qty'];
                    $product = $this->_initProductWithId($item['product_id']);

                    if (isset($params['qty'])) {
                        $filter = new \Zend_Filter_LocalizedToNormalized(
                            ['locale' => $this->resolverInterface->getLocale()]
                        );
                        $params['qty'] = $filter->filter($params['qty']);
                    }
                    if (isset($item['product_type']) && $item['product_type'] == 'configurable') {
                        $params['super_attribute'] = $this->makeArrConfigurableAttribute($item['super_attribute']);
                    }

                    if (isset($item['product_type']) && $item['product_type'] == 'bundle') {
                        $bundleOption = $item['bundle_option'];
                        $bundleOptionQty = [];
                        if (isset($item['bundle_option_qty']) && is_array($item['bundle_option_qty'])) {
                            foreach ($item['bundle_option_qty'] as $key => $value) {
                                $bundleOptionQty[$key] = $value;
                            }
                        }
                        $params['bundle_option'] = $bundleOption;
                        $params['bundle_option_qty'] = $bundleOptionQty;
                    }
                    /**
                     * Check product availability
                     */
                    if (!$product) {
                        return $this->goBack($this->getReferUrl($data['riki_course_id']));
                    }
                    $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
                    $minimumQty = $stockItem->getMinSaleQty();

                    if ($params['qty'] < $minimumQty) {
                        $message = sprintf(
                            __('The purchase quantity of product: %s must be equal or greater than %s'),
                            $product->getName(), $minimumQty);
                        $this->messageManager->addErrorMessage($message);
                        return $this->goBack($this->getReferUrl($data['riki_course_id']));
                    }

                    if (!$this->stockState->canAssigned($product, $params['qty'], $this->stockState->getPlaceIds())) {
                        $message = sprintf(
                            __("We don't have as many \"%s\" as you requested. (Sub)"),
                            $product->getName());
                        $this->messageManager->addError($message);
                        return $this->goBack($this->getReferUrl($data['riki_course_id']));
                    }

                    $this->cart->addProduct($product, $params, $this->getReferUrl($data['riki_course_id']));
                    if ($product) {
                        $arrProduct[] = $product;
                        $arrProductIds[] = $product->getId();
                    }
                }
            }

            $this->prepareSubscriptionData($quote, $data);

            $this->addFreeMachine($data, $arrProductIds, $quote);
            $this->addMultipleMachine($data, $quote);

            $this->deliveryTypeHelper->setDeliveryTypeForQuote($quote);

            // Turn off delivery_type_flag
            // Need to set delivery type again for case subscription meet conditions to add promotion item
            $quote->setData(\Riki\DeliveryType\Model\Delitype::DELIVERY_TYPE_FLAG, false);

            // Validate order total amount threshold
            try {
                $this->cart->setValidateMaxMinCourse(true);
                $this->cart->save();
                $listGiftWrapping = $this->getListGiftWrapping($this->getRequest()->getParams());

                if (count($listGiftWrapping) > 0) {
                    foreach ($quote->getAllVisibleItems() as $item) {
                        if (isset($listGiftWrapping[$item->getProductId()])) {
                            $this->updateWrappingItem($listGiftWrapping[$item->getProductId()], $item);
                        }
                    }
                }

            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $this->goBack($this->getReferUrl($data['riki_course_id']));
            }

            $this->removeWishList($arrProduct);
            return $this->goBack(null, $arrProduct);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNotice(
                    $this->escaper->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addError(
                        $this->escaper->escapeHtml($message)
                    );
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);

            if (!$url) {
                $cartUrl = $this->cartHelper->getCartUrl();
                $url = $this->_redirect->getRedirectUrl($cartUrl);
            }

            return $this->goBack($url);
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
            $this->loggerInterface->critical($e);
            return $this->goBack();
        }
    }

    public function addMultipleMachine($data, $quote)
    {
        if (isset($data['subscription_type']) &&
            $data['subscription_type'] == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
            if (isset($data['machines'])) {
                $machines = $data['machines'];
                foreach ($machines as $typeId => $machineId) {
                    $product = $this->_initProductWithId($machineId);
                    if (!$product) {
                        return $this->goBack($this->getReferUrl($data['riki_course_id']));
                    }
                    $unitQty = 1;
                    if ($product->getData('case_display')) {
                        if (\Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY ==
                            $product->getData('case_display')) {
                            $unitQty = $product->getUnitQty() ? $product->getUnitQty() : 1;
                        }
                    }

                    // add new machine to cart
                    $requestInfo = [
                        'qty' => $unitQty,
                        'options' => [
                            'machine_type_id' => $typeId,
                            'qty' => $unitQty
                        ],
                        'is_multiple_machine' => true
                    ];
                    $product = clone $product;
                    $product->addCustomOption('machine_type_id', $typeId);
                    $this->cart->addProduct($product, new \Magento\Framework\DataObject($requestInfo));

                    $quoteItem = $quote->getItemByProduct($product);
                    if ($quoteItem) {
                        $quoteItem->setData('is_riki_machine', true);
                    }
                }
            }
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $data
     * @return $this
     * @throws NoSuchEntityException
     */
    protected function prepareSubscriptionData(
        \Magento\Quote\Model\Quote $quote,
        array $data
    )
    {
        $course = $this->courseRepository->get($data[Constant::RIKI_COURSE_ID]);

        $quote->setData('riki_course_id', $data[Constant::RIKI_COURSE_ID]);
        $quote->setData(Constant::RIKI_FREQUENCY_ID, (int)$data['frequency']);
        /*RMM-375 add trial point for quote */
        if ($course->getData('point_for_trial') > 0) {
            $quote->setData('point_for_trial', $course->getData('point_for_trial'));
        }

        if ($course->getSubscriptionType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
            if ($quote->getData(Constant::RIKI_HANPUKAI_QTY)) {
                $hanpukaiChangeSetQty
                    = $quote->getData(Constant::RIKI_HANPUKAI_QTY) + $data['hanpukai_change_set_qty'];
                $quote->setData(Constant::RIKI_HANPUKAI_QTY, $hanpukaiChangeSetQty);
            } else {
                $quote->setData(Constant::RIKI_HANPUKAI_QTY, $data['hanpukai_change_set_qty']);
            }
        }

        $quote->setData('allow_choose_delivery_date', $course->isAllowChooseDeliveryDate());

        return $this;
    }

    /**
     * @param $data
     * @return bool
     */
    public function checkMachineHaveOnMachineType($data)
    {
        if (isset($data['subscription_type']) &&
            $data['subscription_type'] == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
            $quote = $this->cart->getQuote();

            if (!isset($data['machines']) && $this->helperMachine->hasMachineInCart($quote)) {
                return true;
            }
            if (isset($data['machines'])) {
                foreach ($data['machines'] as $typeId => $machineId) {
                    $machineIds = $this->helperMachine->getMachinesByMachineType($typeId);
                    if (!empty($machineIds)) {
                        foreach ($machineIds as $machine) {
                            if ($machine['product_id'] == $machineId) {
                                return true;
                            }
                        }
                    }
                }
            }
            return false;
        }
        return true;
    }

    /**
     * Add free machine
     */
    public function addFreeMachine($data, $arrProductIds, $quote)
    {
        if (isset($data['machine']) && $data['machine']) {
            $machineId = $data['machine'];
            $product = $this->_initProductWithId($machineId);
            if (!$product) {
                return $this->goBack($this->getReferUrl($data['riki_course_id']));
            }
            if (!in_array($machineId, $this->cart->getProductIds()) && !in_array($machineId, $arrProductIds)) {
                $machine = $this->_courseModel->getMachine($data['riki_course_id'], $machineId);
                if (!$machine) {
                    return $this->goBack($this->getReferUrl($data['riki_course_id']));
                }

                $unitQty = 1;
                if ($product->getData('case_display')) {
                    if (\Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY ==
                        $product->getData('case_display')) {
                        $unitQty = $product->getUnitQty() ? $product->getUnitQty() : 1;
                    }
                }

                $params['qty'] = $unitQty;

                // remove current machine in cart
                $allItems = $this->cart->getItems();
                foreach ($allItems as $item) {
                    if ($item->getData('is_riki_machine') == 1) {
                        $this->cart->removeItem($item->getId());
                    }
                }
                // add new machine to cart
                $this->cart->addProduct($product, $params);

                $quoteItem = $quote->getItemByProduct($product);
                if ($quoteItem) {
                    $quoteItem->setData('is_riki_machine', true);
                }
            }
        }
    }

    /**
     * @param $courseId
     *
     * @return string
     */
    public function getReferUrl($courseId)
    {
        return $this->_urlBuilder->getUrl('subscription-page/view/index', ['id' => $courseId]);
    }

    public function removeWishList($arrProduct)
    {
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

    public function validateAddToCart($data, $multiMachineNotRequired)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $prepareData = $this->prepareProductData($data);

        if (empty($prepareData)) {
            $message = __('Please select your desired machine and product');
            $this->messageManager->addErrorMessage($message);
            return 1;
        }
        
        if (!$this->isHaveCourseId($data['riki_course_id'])) {
            $message = __('Only one subscription allowed in the shopping cart');
            $this->messageManager->addErrorMessage($message);
            return 1;
        }

        if (!$multiMachineNotRequired) {
            if (!$this->checkMachineHaveOnMachineType($data)) {
                $message = __('There is an invalid machine');
                $this->messageManager->addErrorMessage($message);
                return 1;
            }
        }

        if (!$data['frequency']) {
            $message = __('Please select the interval of the subscription.');
            $this->messageManager->addErrorMessage($message);
            return 1;
        }


        if ($this->isSpotProduct($data, $data['riki_course_id']) == 1) {
            $message = __('shopping cart cannot contain SPOT and subscription at the same time');
            $this->messageManager->addErrorMessage($message);
            return 1;
        }

        /** Validate maximum qty restriction */
        $prepareDataValidateMaximumQty = $this->subscriptionValidator->prepareProductData($prepareData);
        $validateMaximumQty = $this->subscriptionValidator
            ->setCourseId($data['riki_course_id'])
            ->setProductCarts($prepareDataValidateMaximumQty)
            ->validateMaximumQtyRestriction();

        if ($validateMaximumQty['error']) {
            $message = $this->subscriptionValidator->getMessageMaximumError(
                $validateMaximumQty['product_errors'],
                $validateMaximumQty['maxQty']
            );

            $this->messageManager->addErrorMessage($message);
            return 1;
        }

        $arrResultMustHaveQtyInCategory = $this->checkMustHaveQtyInCategory($prepareData, $data['riki_course_id']);
        if ($arrResultMustHaveQtyInCategory['has_error'] == 1) {
            $this->messageManager->addError(__(
                'You need to purchase %1 items of %2',
                $arrResultMustHaveQtyInCategory['qty_category'],
                $arrResultMustHaveQtyInCategory['category_name']
            ));
            return 1;
        }

        // Check minimum order qty
        $arrResultCheckMinimumOrderQty = $this->checkMinimumOrderQty($prepareData, $data['riki_course_id']);
        if ($arrResultCheckMinimumOrderQty['has_error'] == 1) {
            $courseName = null;
            $courseModel = $this->subscriptionPageHelper->getSubscriptionCourseModelFromCourseId($data['riki_course_id']);
            if ($courseModel->getId()) {
                $courseName = $courseModel->getData('course_name');
            }
            $message = __("In %1, the total number of items in the shopping cart have at least %2 quantity",
                $courseName, $arrResultCheckMinimumOrderQty['minimum_order_qty']);
            $this->messageManager->addError($message);
            return 1;
        }

        if ($this->_customerSession->isLoggedIn()) {
            $arrResultCheckApplicationLimit = $this->subscriptionPageHelper->checkApplicationLimit($this->_customerSession->getCustomerId(),
                $data['riki_course_id']);
            if ($arrResultCheckApplicationLimit['has_error'] == 1) {
                $errorMessage = $this->subscriptionPageHelper->getApplicationLimitErrorMessage(
                    $arrResultCheckApplicationLimit
                );
                $this->messageManager->addError($errorMessage);
                return 1;
            }
        }

        $totalQtyInFO = $this->calculateTotalQtyInFo($prepareData);
        $maximumOrderQtyConfig = $this->getConfig(AdvancedInventoryStock::ADVANCED_INVENTORY_MAXIMUM_CART_STOCK);
        if ($maximumOrderQtyConfig > 0 && $maximumOrderQtyConfig < $totalQtyInFO) {
            $message = sprintf(__('I am sorry. <br> In the Nestle Online shopping online shop, we have restricted the maximum number of items as %s at one order. Sorry to trouble you, but please change the number of items in the cart to %s pieces or less.'), $maximumOrderQtyConfig, $maximumOrderQtyConfig);
            $this->messageManager->addError($message);
            return 1;
        }

        if (isset($data['riki_course_id']) && !$this->_registry->registry(Constant::RIKI_COURSE_ID)) {
            $this->_registry->register(Constant::RIKI_COURSE_ID, $data['riki_course_id']);
        }
        if (isset($data['frequency']) && !$this->_registry->registry(Constant::RIKI_FREQUENCY_ID)) {
            $this->_registry->register(Constant::RIKI_FREQUENCY_ID, $data['frequency']);
        }

        return 0;
    }

    /**
     * Prepare Data For BackOrder
     *
     * @param $prepareData
     *
     * @return array
     */
    public function prepareDataForBackOrder($prepareData)
    {
        $arrResult = array();
        foreach ($prepareData as $productId => $dataInfo) {
            $arrResult[$productId]['qty'] = $dataInfo['piece_qty'];
            $arrResult[$productId]['assignation'] = $dataInfo['assignation'];
            $arrResult[$productId]['product'] = $dataInfo['product'];
        }
        return $arrResult;
    }

    /**
     * Calculate total qty info
     *
     * @param $prepareData
     *
     * @param $prepareData
     * @return int
     */
    public function calculateTotalQtyInFo($prepareData)
    {
        $totalProductBought = 0;
        foreach ($prepareData as $productId => $productInfo) {
            $totalProductBought = $totalProductBought + $productInfo['qty'];
        }

        return $totalProductBought;
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
        return $this->_scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Resolve response
     *
     * @param string $backUrl
     * @param \Magento\Catalog\Model\Product $product
     * @return $this|\Magento\Framework\Controller\Result\Redirect
     */
    protected function goBack($backUrl = null, $arrProduct = null, $notify = '')
    {
        if (!$this->getRequest()->isAjax()) {
            $backUrl = parent::_goBack($backUrl);
            $backUrl->setUrl($this->_helperLineApp->checkRequestAddParam($this->getBackUrl()));
            return $backUrl;
        }

        $result = [];
        $result['notify'] = $notify;

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
            $this->jsonHelperData->jsonEncode($result)
        );
    }

    /**
     * @param $arrConfigurable
     * @return array
     */
    public function makeArrConfigurableAttribute($arrConfigurable)
    {
        $result = array();
        foreach ($arrConfigurable as $attributeId => $attributeName) {
            $result[$attributeId] = $attributeName;
        }
        return $result;
    }

    public function isHaveCourseId($currentRikiCourseId)
    {
        $oldRikiCourseId = $this->_checkoutSession->getQuote()->getData('riki_course_id');
        if ($oldRikiCourseId) {
            if ($currentRikiCourseId != $oldRikiCourseId) {
                return false;
            }
        }
        return true;
    }

    public function isSpotProduct($dataProductPost, $currentCourseId)
    {

        $arrAllProductId = [];
        $productAllCart = $this->cart->getQuote()->getAllVisibleItems();
        foreach ($productAllCart as $item) {
            $buyRequest = $item->getBuyRequest();
            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                continue;
            }
            if ($item->getData('is_riki_machine') == 1) {
                continue;
            }
            $arrAllProductId[] = $item->getProductId();
        }

        if ($arrAllProductId && !$this->cart->getQuote()->getData('riki_course_id')) {
            return 1;
        }

        foreach ($dataProductPost['data']['product'] as $item) {
            if (isset($item['qty']) && $item['qty'] > 0) {
                $arrAllProductId[] = $item['product_id'];
            }
        }

        /**
         * Check for hanpukai
         */
        if ($this->subscriptionPageHelper->getSubscriptionType($currentCourseId)
            == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
            $hanpukaiType = $this->subscriptionPageHelper->getHanpukaiType($currentCourseId);
            $productFromCourseHanpukai = $this->subscriptionPageHelper->getHanpukaiProductId($hanpukaiType, $currentCourseId);

            $countAllProductIds = count($arrAllProductId);
            for ($i = 0; $i < $countAllProductIds; $i++) {
                if (!in_array($arrAllProductId[$i], $productFromCourseHanpukai)) {
                    return 1;
                }
            }

            return 0;
        }

        $subscriptionCourseHelper = $this->subscriptionCourseHelper;
        return $subscriptionCourseHelper->checkCartIsValidForCourse($arrAllProductId, $currentCourseId);

    }

    /**
     * CheckAdditionalProduct
     *
     * @param $dataProductPost
     * @return bool
     */
    public function checkAdditionalProduct($dataProductPost)
    {

        $isMainProductInPost = false;
        $isMainProductInCart = false;

        if (isset($dataProductPost['data']) && isset($dataProductPost['data']['product'])) {
            foreach ($dataProductPost['data']['product'] as $item) {
                if (isset($item['qty']) && $item['qty'] > 0) {
                    if (isset($item['is_addition']) && !$item['is_addition']) {
                        $isMainProductInPost = true;
                        break;
                    }
                }
            }
        }


        $productAllCart = $this->cart->getQuote()->getAllVisibleItems();
        if ($productAllCart) {
            foreach ($productAllCart as $item) {
                if ($item->hasData('is_addition') && !$item->getData('is_addition')) {
                    $isMainProductInPost = true;
                    break;
                }
            }
        }


        if (!$isMainProductInCart && !$isMainProductInPost) {
            return false;
        }

        return true;
    }

    /**
     * Check minimum order qty
     *
     * @param $prepareData
     * @param $courseId
     *
     * @return array
     */
    public function checkMinimumOrderQty($prepareData, $courseId)
    {
        $arrResult = ['has_error' => 0, 'total_qty_bought' => 0];
        $totalProductBought = 0;
        foreach ($prepareData as $productId => $productInfo) {
                $totalProductBought = $totalProductBought + $productInfo['qty'];
        }
        $courseModel = $this->subscriptionPageHelper->getSubscriptionCourseModelFromCourseId($courseId);
        $arrResult['minimum_order_qty'] = $courseModel->getData('minimum_order_qty');
        if ($totalProductBought < (int)$courseModel->getData('minimum_order_qty')) {
            $arrResult['has_error'] = 1;
            return $arrResult;
        }

        return $arrResult;
    }

    /**
     * Check Must Have Qty In Category
     *
     * @param $prepareData
     * @param $courseId
     *
     * @return array
     */
    public function checkMustHaveQtyInCategory($prepareData, $courseId)
    {
        $arrResult = ['has_error' => 0, 'qty_category' => 0, 'category_name' => '', 'course_name' => ''];
        $arrProductIdQty = [];
        foreach ($prepareData as $productId => $productInfo) {
            $arrProductIdQty[$productId] = $productInfo['qty'];
        }

        $courseModel = $this->subscriptionPageHelper->getSubscriptionCourseModelFromCourseId($courseId);
        $mustHaveCatId = $courseModel->getData("must_select_sku");
        $isValid = $this->subscriptionCourseHelper->isValidMustHaveQtyInCategory($arrProductIdQty, $mustHaveCatId);
        if (!$isValid) {
            $arrCategoryIdQty = explode(':', $mustHaveCatId);
            $categoryName = '';
            $categoryQtyConfig = 0;
            if (count($arrCategoryIdQty) > 1) {
                try {
                    $categoryName = $this->categoryRepository->get($arrCategoryIdQty[0])->getName();
                } catch (\Exception $e) {
                    $this->messageManager->addError('No such category id');
                    return $arrResult;
                }
                $categoryQtyConfig = $arrCategoryIdQty[1];
            }
            $arrResult['has_error'] = 1;
            $arrResult['qty_category'] = $categoryQtyConfig;
            $arrResult['category_name'] = $categoryName;
            $arrResult['course_name'] = $courseModel->getData('course_name');
        }
        return $arrResult;
    }

    /**
     * Prepare product data
     *
     * @param $dataProductPost
     *
     * @return array
     */
    public function prepareProductData($dataProductPost)
    {
        $arrResult = array();
        $quote = $this->_checkoutSession->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $buyRequest = $item->getBuyRequest();
            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                continue;
            }
            if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE) {
                if (!array_key_exists($item->getProduct()->getId(), $arrResult)) {
                    $arrResult[$item->getProduct()->getId()]['qty'] = $item->getQty();
                    $arrResult[$item->getProduct()->getId()]['product'] = $item->getProduct();
                    $arrResult[$item->getProduct()->getId()]['assignation'] = '';
                    $arrResult[$item->getProduct()->getId()]['is_addition'] = $item->getData('is_addition');
                    $arrResult[$item['product_id']]['piece_qty'] = $item->getQty();
                } else {
                    $arrResult[$item->getProduct()->getId()]['qty']
                        = $arrResult[$item->getProduct()->getId()]['qty'] + $item->getQty();
                    $arrResult[$item['product_id']]['piece_qty'] = $arrResult[$item['product_id']]['piece_qty'] + $item->getQty();
                }
            }

            if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                if (!array_key_exists($item->getProduct()->getId(), $arrResult)) {
                    $arrResult[$item->getProduct()->getId()]['qty'] = ($item->getQty() / $item->getUnitQty());
                    $arrResult[$item->getProduct()->getId()]['product'] = $item->getProduct();
                    $arrResult[$item->getProduct()->getId()]['assignation'] = '';
                    $arrResult[$item->getProduct()->getId()]['is_addition'] = $item->getData('is_addition');
                    $arrResult[$item['product_id']]['piece_qty'] = $item->getQty();
                } else {
                    $arrResult[$item->getProduct()->getId()]['qty']
                        = $arrResult[$item->getProduct()->getId()]['qty'] + ($item->getQty() / $item->getUnitQty());
                    $arrResult[$item['product_id']]['piece_qty'] = $arrResult[$item['product_id']]['piece_qty'] + $item->getQty();
                }
            }
        }

        foreach ($dataProductPost['data']['product'] as $item) {
            if (isset($item['qty']) && $item['qty'] > 0) {

                if (isset($item['case_display'])) {
                    if (strtoupper($item['case_display']) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        if (!array_key_exists($item['product_id'], $arrResult)) {
                            $arrResult[$item['product_id']]['qty'] = ($item['qty'] / $item['unit_qty']);
                            $arrResult[$item['product_id']]['product'] = $this->_initProductWithId($item['product_id']);
                            $arrResult[$item['product_id']]['assignation'] = '';
                            $arrResult[$item['product_id']]['is_addition'] = $item['is_addition'];
                            $arrResult[$item['product_id']]['piece_qty'] = $item['qty'];
                        } else {
                            $arrResult[$item['product_id']]['qty']
                                = $arrResult[$item['product_id']]['qty'] + ($item['qty'] / $item['unit_qty']);
                            $arrResult[$item['product_id']]['piece_qty'] = $item['qty'] + $arrResult[$item['product_id']]['piece_qty'];
                        }
                    } else
                        if (strtoupper($item['case_display']) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE) {
                            if (!array_key_exists($item['product_id'], $arrResult)) {
                                $arrResult[$item['product_id']]['qty'] = $item['qty'];
                                $arrResult[$item['product_id']]['product'] = $this->_initProductWithId($item['product_id']);
                                $arrResult[$item['product_id']]['assignation'] = '';
                                $arrResult[$item['product_id']]['is_addition'] = $item['is_addition'];
                                $arrResult[$item['product_id']]['piece_qty'] = $item['qty'];
                            } else {
                                $arrResult[$item['product_id']]['qty']
                                    = $arrResult[$item['product_id']]['qty'] + $item['qty'];
                                $arrResult[$item['product_id']]['piece_qty'] = $arrResult[$item['product_id']]['piece_qty'] + $item['qty'];

                            }
                        }
                } else {
                    if (!array_key_exists($item['product_id'], $arrResult)) {
                        $arrResult[$item['product_id']]['qty'] = $item['qty'];
                        $arrResult[$item['product_id']]['product'] = $this->_initProductWithId($item['product_id']);
                        $arrResult[$item['product_id']]['assignation'] = '';
                        $arrResult[$item['product_id']]['is_addition'] = $item['is_addition'];
                        $arrResult[$item['product_id']]['piece_qty'] = $item['qty'];
                    } else {
                        $arrResult[$item['product_id']]['qty']
                            = $arrResult[$item['product_id']]['qty'] + $item['qty'];
                        $arrResult[$item['product_id']]['piece_qty'] = $arrResult[$item['product_id']]['piece_qty'] + $item['qty'];
                    }
                }
            }
        }

        return $arrResult;
    }


    public function mergeItemSameId($data)
    {
        $arrResult = [];
        $arrDataProduct = $data['data']['product'];
        foreach ($arrDataProduct as $item) {
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
     * @param $productList
     * @return array
     */
    private function getListGiftWrapping($productList) {
        $listGiftWrapping = [];
        foreach($productList['data']['product'] as $item ) {
            if ($item['gift_wrapping'] > 0) {
                $listGiftWrapping[$item['product_id']] = $item['gift_wrapping'];
            }
        }
        return $listGiftWrapping;
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
}
