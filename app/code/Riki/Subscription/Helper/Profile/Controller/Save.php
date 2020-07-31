<?php

namespace Riki\Subscription\Helper\Profile\Controller;

use Magento\Framework\DataObject;
use Riki\Subscription\Model\Constant;
use Riki\AdvancedInventory\Model\Stock as AdvancedInventoryStock;

class Save
{
    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    protected $strRedirectWhenFail;

    protected $strRedirectWhenConfirm;

    protected $strRedirectWhenProfileNotExists;

    protected $_authSession;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Riki\Subscription\Helper\CalculateDeliveryDate
     */
    protected $_calculateDeliveryDateHelper;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_datetime;

    /**
     * @var \Riki\Subscription\Model\Frequency\Frequency
     */
    protected $frequencySubModel;

    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $helperDelivery;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;
    /**
     * @var \Riki\DelayPayment\Helper\Data
     */
    protected $delayHelper;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subOrderHelper;

    /**
     * @var array
     */
    protected $allCartProductItems = [];

    /**
     * @var array
     */
    protected $newlyAddedProducts = [];

    /**
     * @var array
     */
    protected $productDeliveryTypes = [];

    protected $subscriptionValidator;

    /**
     * Save constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory
     * @param \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
     * @param \Riki\Subscription\Model\Paygent $paygentModel
     * @param \Riki\Subscription\Api\WebApi\ProfileRepositoryInterface $profileRepository
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Riki\SubscriptionCourse\Helper\Data $helperCourse
     * @param \Riki\Subscription\Helper\Data $helperSubscription
     * @param \Riki\DeliveryType\Helper\Data $helperDelivery
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Riki\Subscription\Helper\CalculateDeliveryDate $calculateDeliveryDate
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Riki\Subscription\Model\Frequency\Frequency $frequencySubModel
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     * @param \Riki\DelayPayment\Helper\Data $delayHelper
     * @param \Riki\Subscription\Helper\Order $subOrderHelper
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Framework\App\State $state,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Riki\Subscription\Model\Paygent $paygentModel,
        \Riki\Subscription\Api\WebApi\ProfileRepositoryInterface $profileRepository,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\SubscriptionCourse\Helper\Data $helperCourse,
        \Riki\Subscription\Helper\Data $helperSubscription,
        \Riki\DeliveryType\Helper\Data $helperDelivery,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Riki\Subscription\Helper\CalculateDeliveryDate $calculateDeliveryDate,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Riki\Subscription\Model\Frequency\Frequency $frequencySubModel,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\DelayPayment\Helper\Data $delayHelper,
        \Riki\Subscription\Helper\Order $subOrderHelper,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        $this->_scopeConfig = $scopeConfigInterface;
        $this->_categoryFactory = $categoryFactory;
        $this->appState = $state;
        $this->customerFactory = $customerFactory;
        $this->customerResourceFactory = $customerResourceFactory;
        $this->redirectFactory = $redirectFactory;
        $this->paygentModel = $paygentModel;
        $this->profileRepository = $profileRepository;
        $this->helperProfile = $helperProfile;
        $this->helperCourse = $helperCourse;
        $this->helperSubscription = $helperSubscription;
        $this->helperDelivery = $helperDelivery;
        $this->_authSession = $authSession;
        $this->_customerSession = $customerSession;
        $this->_calculateDeliveryDateHelper = $calculateDeliveryDate;
        $this->_productRepository = $productRepository;
        $this->_datetime = $datetime;
        $this->frequencySubModel = $frequencySubModel;
        $this->logger = $logger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->delayHelper = $delayHelper;
        $this->subOrderHelper = $subOrderHelper;
        $this->subscriptionValidator = $subscriptionValidator;
    }

    /**
     * @param \Riki\Subscription\Controller\Profile\Save $action
     * @return mixed
     */
    public function execute($action)
    {
        $arrParams = $action->getRequest()->getPostValue();

        $strRedirectWhenFail = $action->getStrRedirectWhenFail();
        $this->strRedirectWhenFail = $strRedirectWhenFail;
        $strRedirectWhenConfirm = $action->getStrRedirectWhenConfirm();
        $this->strRedirectWhenConfirm = $strRedirectWhenConfirm;
        $strRedirectWhenProfileNotExists = $action->getStrRedirectWhenProfileNotExists();
        $this->strRedirectWhenProfileNotExists = $strRedirectWhenProfileNotExists;

        $objMessageManager = $action->getMessageManager();

        $profileId = $action->getRequest()->getParam('profile_id');
        $profileIdReturn = $this->helperProfile->getProfileOriginFromTmp($profileId);
        $objProfileCache = $action->getProfileCache();
        $arrProductCartCache = !empty($objProfileCache)? $this->preparedProfileCartItemData($objProfileCache) : [];

        $isBackBtnPressed = isset($arrParams['save_profile']) && $arrParams['save_profile'] === 'back';
        $isConfirmPressed = isset($arrParams['save_profile']) && $arrParams['save_profile'] == 'confirm';

        try {
            /** validate stock point */
            $messageError = $this->validateStockPoint($objProfileCache, $arrProductCartCache, $arrParams);
            if (!empty($messageError)) {
                $objMessageManager->addError($messageError);
                return $action->_redirect($strRedirectWhenFail, ['id' => $profileIdReturn]);
            }

            /*validate lost session*/
            if (empty($objProfileCache)) {
                $objMessageManager->addError(__('Something went wrong, please reload page.'));
                return $action->_redirect($strRedirectWhenFail, ['id' => $profileIdReturn]);
            }

            /*validate critical error*/
            $isValidCritical = $this->validateCriticalError($action, $arrParams, $profileId, $objProfileCache);
            if (!$isValidCritical) {
                $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
                $action->saveToCache($objProfileCache);
                return $action->_redirect($strRedirectWhenFail, ['id' => $profileIdReturn]);
            }

            /*when clicking back button to edit page*/
            if ($isBackBtnPressed) {
                $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
                $action->saveToCache($objProfileCache);
                return $action->_redirect($strRedirectWhenFail, ['id' => $profileIdReturn]);
            }

            /*validate common error*/

            $paymentMethod = isset($arrParams['payment_method']) ? $arrParams['payment_method'] : $objProfileCache->getData("payment_method");
            $arrAllProductCatIdByAddrByDL = isset($arrParams['productcat_id']) ? $arrParams['productcat_id'] : [];
            $isSkipNextDelivery = isset($arrParams['skip_next_delivery']) ?  $arrParams['skip_next_delivery'] == 'on' : $objProfileCache->getData("skip_next_delivery");
            $earnPoint = isset($arrParams['earn_point_on_order']) ?  $arrParams['earn_point_on_order'] == 'on' : $objProfileCache->getData("earn_point_on_order");
            $frequencyId = isset($arrParams['frequency_id']) ? $arrParams['frequency_id'] : $this->helperSubscription->getFrequencyIdByUnitAndInterval($objProfileCache->getData("frequency_unit"), $objProfileCache->getData("frequency_interval"));

            if (!empty($arrProductCartCache) && $paymentMethod == \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_COD
            ) {
                $this->checkDmOnly($action, $arrProductCartCache);
            }

            /*validate delivery date*/
            $this->validateDeliveryDate($action, $arrParams);

            /*validate frequency*/
            $objFrequency = $this->frequencySubModel->load($frequencyId);
            $isValidFrequency = $this->validateFrequency($action, $objFrequency);

            /*validate customer address*/
            $this->validateCustomerAddress($action, $arrParams, $objProfileCache);

            /*validate product cart*/
            $isValidCartItem = $this->validateProfileCart($action, $arrParams, $objProfileCache);

            /*set profile data into session*/
            if ($isValidFrequency) {
                $objProfileCache->setData("frequency_unit", $objFrequency->getData("frequency_unit"));
                $objProfileCache->setData("frequency_interval", $objFrequency->getData("frequency_interval"));
            }

            if ($paymentMethod == 'new_paygent') {
                $paymentMethod = 'paygent';
                $objProfileCache->setData('is_new_paygent_method', true);
            } else {
                $objProfileCache->setData('is_new_paygent_method', false);
            }

            $objProfileCache->setData("payment_method", $paymentMethod);
                $objProfileCache->setData("skip_next_delivery", $isSkipNextDelivery);
            $objProfileCache->setData("earn_point_on_order", $earnPoint);
            $objProfileCache->setData('updated_at', (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));

            // If exists shipping_fee then add it to profile
            if (isset($arrParams['shipping_fee'])) {
                $objProfileCache->setData("shipping_fee", $arrParams["shipping_fee"]);
            }

            if ($isValidCartItem) {
                $oldProductCart = $objProfileCache->getData(Constant::CACHE_PROFILE_PRODUCT_CART);
                $oldProductCart = $this->subOrderHelper->cloneProductCartData($oldProductCart);
                $this->saveProductCartInfo($arrAllProductCatIdByAddrByDL, $arrProductCartCache, $objMessageManager, $arrParams);
                $arrProductCartCache = $this->removeDuplicateAndMerge($arrProductCartCache);
                $objProfileCache->setData(Constant::CACHE_PROFILE_PRODUCT_CART, $arrProductCartCache);
                $subscriptionCourse = $this->subOrderHelper->loadCourse($objProfileCache->getData('course_id'));

                /**
                 * Can not validate min,max for submit coupon.add product subscription
                 */
                $notValidateAmount = isset($arrParams['not_validate_amount']) ? true : false ;
                $validateResults = $this->subOrderHelper->validateSimulateOrderAmountRestriction(
                    $subscriptionCourse,
                    $objProfileCache
                );
                if (!$validateResults['status'] && !$notValidateAmount) {
                    $objProfileCache->setData(Constant::CACHE_PROFILE_PRODUCT_CART, $oldProductCart);
                    $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
                    $objMessageManager->addError($validateResults['message']);
                    return $action->_redirect($strRedirectWhenFail, ['id' => $profileIdReturn]);
                }
            }

            /** Validate maximum qty restriction */
            $productCarts = $objProfileCache->getProductCart();
            $validateMaximumQty = $this->subscriptionValidator->setProfileId($profileId)
                ->setProductCarts($productCarts)
                ->validateMaximumQtyRestriction();
            if ($validateMaximumQty['error'] && !empty($validateMaximumQty['product_errors'])) {
                $message = $this->subscriptionValidator->getMessageMaximumError(
                    $validateMaximumQty['product_errors'],
                    $validateMaximumQty['maxQty']
                );
                $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
                $objMessageManager->addError($message);
                return $action->_redirect($strRedirectWhenFail, ['id' => $profileIdReturn]);
            }

            if ($objMessageManager->hasMessages()) {
                $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
                $action->saveToCache($objProfileCache);
                return $action->_redirect($strRedirectWhenFail, ['id' => $profileIdReturn]);
            }

            /*save profile data from session into db if click confirmed*/
            if ($isConfirmPressed) {
                $objProfile  = $this->helperProfile->load($profileId);
                $redirectResult = $this->confirmedAllChange($profileId, $objProfile, $objProfileCache, false, $paymentMethod, $objMessageManager, $arrParams);
                if ($redirectResult === true) {
                    $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
                    $action->saveToCache($objProfileCache);
                    $objMessageManager->addSuccess(__("Update profile successfully!"));
                    return $action->_redirect($this->strRedirectWhenConfirm, ['id' => $profileIdReturn]);
                } else {
                    return $action->_redirect($redirectResult, ['id'=>$profileIdReturn]);
                }
            }
            /** Save cache */
            $action->saveToCache($objProfileCache);
        } catch (\Exception $e) {
            $objMessageManager->addError($e->getMessage());
            $this->logger->critical($e);
        }


        $action->_redirect($strRedirectWhenFail, ['id' => $profileIdReturn]);
    }

    /**
     * Validate critical error
     *
     * @param \Riki\Subscription\Controller\Profile\Save $action
     * @param $arrParams
     * @param $profileId
     * @param \Riki\Subscription\Model\Profile\Profile $objProfileCache
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateCriticalError($action, $arrParams, $profileId, $objProfileCache){

        $objMessageManager = $action->getMessageManager();
        $isUpdatePressed =  isset($arrParams['save_profile']) && $arrParams['save_profile'] === 'update';
        $arrProductCartSession = !empty($objProfileCache)? $this->preparedProfileCartItemData($objProfileCache):[];

        /*validate profile id*/
        if (! ($profileId)) {
            return false;
        }

        try {
            $objProfile = $this->helperProfile->load($profileId);
            if (!$objProfile || !$objProfile->getId()) {
                $objMessageManager->addError(__('Cannot found this profile'));
                return false;
            }
        } catch (\Exception $e) {
            $objMessageManager->addError(__('Cannot found this profile'));
            return false;
        }

        $objFormKeyValidator = $action->getFormkeyValidator();
        if (! $objFormKeyValidator->validate($action->getRequest())) {
            return false;
        }

        if (! $action->getRequest()->isPost() || empty($arrParams)) {
            return false;
        }

        /*set data for session from old code */
        if ($isUpdatePressed) {
            $arrParams['save_prederred'] = isset($arrParams['save_prederred']) ? $arrParams['save_prederred'] : '';
            $objProfileCache->setData('paygent_save_prederred', $arrParams['save_prederred']);

            $arrParams['new_paygent'] = (isset($arrParams['payment_method']) && $arrParams['payment_method'] == 'new_paygent') ? 1 : '';
            $objProfileCache->setData('new_paygent', $arrParams['new_paygent']);

            $objProfileCache->setData('address', isset($arrParams['address']) ? $arrParams['address'] : null);
            $objProfileCache->setData('profile_type', $arrParams['profile_type']);

            $arrParams['skip_next_delivery'] = isset($arrParams['skip_next_delivery']) ? $arrParams['skip_next_delivery'] : '';
            $objProfileCache->setData('skip_next_delivery', $arrParams['skip_next_delivery'] == 'on');
        }

        /*validate permission*/
        $customerId = $objProfileCache->getData("customer_id");
        if (! $this->helperProfile->isHaveViewProfilePermission($customerId, $profileId)) {
            $objMessageManager->addError(__("Access denied"));
            return false;
        }

        /*validate profile cart data is not empty*/
        if (is_array($arrProductCartSession) and empty($arrProductCartSession)) {
            $objMessageManager->addError(__("Subscription profile must have at least one product."));
            return false;
        }

        /*set default flag for updating*/
        if ($isUpdatePressed) {
            $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '1');
        }

        return true;
    }

    /**
     * @param $action
     * @param $arrParams
     * @return bool
     */
    public function validateDeliveryDate($action, $arrParams){

        $objMessageManager = $action->getMessageManager();

        $arrNextDeliveryByAddrADL = isset($arrParams['next_delivery']) ? $arrParams['next_delivery'] : [];
        if (!empty($arrNextDeliveryByAddrADL)) {
            foreach ($arrNextDeliveryByAddrADL as $addressId => $arrNextDeliveryByDL) {
                foreach ($arrNextDeliveryByDL as $deliveryType => $value) {
                    list($year, $month, $day) = sscanf($value, '%d-%d-%d');
                    if (!checkdate($month, $day, $year)) {
                        $objMessageManager->addError(__("Delivery date invalid format"));
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param \Riki\Subscription\Controller\Profile\Save $action
     * @param $objFrequency
     * @return bool
     */
    public function validateFrequency($action, $objFrequency){

        $objMessageManager = $action->getMessageManager();

        if (empty($objFrequency) || empty($objFrequency->getId())) {
            $objMessageManager->addError(__("Please choice frequency"));
            return false;
        }

        return true;
    }

    /**
     * @param \Riki\Subscription\Controller\Profile\Save $action
     * @param $arrParams
     * @param \Riki\Subscription\Model\Profile\Profile $objProfileCache
     */
    public function validateCustomerAddress($action, $arrParams, $objProfileCache){

        $objMessageManager = $action->getMessageManager();

        if (!$this->checkChangeAddressType($arrParams, $objProfileCache)) {
            $objMessageManager->addError(__('Please change payment method from COD to Paygent to update all changes.'));
        }
    }

    /**
     * @param \Riki\Subscription\Controller\Profile\Save $action
     * @param $arrParams
     * @param \Riki\Subscription\Model\Profile\Profile $objProfileCache $objProfileCache
     * @return bool
     * @throws \Exception
     */
    public function validateProfileCart($action, $arrParams, $objProfileCache)
    {
        $objMessageManager = $action->getMessageManager();
        $arrProductCartSession = !empty($objProfileCache)? $this->preparedProfileCartItemData($objProfileCache) : [];

        /*if only SPOT exist in profile, next delivery the profile will invalid*/
        $productSpot = $this->helperProfile->checkDeleteProductSpot($arrProductCartSession);
        if ($productSpot) {
            $objMessageManager->addError(__("Profile has only SPOT item. You must add at least one subscription item"));
            return false;
        }

        /*validate minimum order qty and must have SKU*/
        $courseId = $objProfileCache->getData('course_id');

        $arrProductId       = $this->getArrProductId($arrProductCartSession);
        $arrProductIdQty = [];
        $hasMainProduct = false;
        $hasAdditional = false;
        $totalQtyInFo = 0;
        $arrParamProductQty =  isset($arrParams['product_qty']) ? $arrParams['product_qty'] : null;
        foreach ($arrProductCartSession as $item) {
            $dataItem = $item->getData();
            // Item is object
            $dataItem['is_addition'] = isset($dataItem['is_addition'])?$dataItem['is_addition']:0;

            if ($dataItem['is_addition'] == 0 and $hasMainProduct == false) {
                $hasMainProduct = true;
            }
            if (is_array($arrParamProductQty) && array_key_exists('qty', $dataItem)
                && array_key_exists($dataItem['cart_id'], $arrParamProductQty)
                && array_key_exists('unit_case', $dataItem) && array_key_exists('unit_qty', $dataItem)) {
                if (strtoupper($dataItem['unit_case']) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                    $totalQtyInFo = $totalQtyInFo + $arrParamProductQty[$dataItem['cart_id']] / $dataItem['unit_qty'];
                    $arrProductIdQty[$dataItem['product_id']] = $arrParamProductQty[$dataItem['cart_id']] / $dataItem['unit_qty'];
                } else {
                    $totalQtyInFo = $totalQtyInFo + $arrParamProductQty[$dataItem['cart_id']];
                    $arrProductIdQty[$dataItem['product_id']] = $arrParamProductQty[$dataItem['cart_id']];
                }
            } else {
                if (strtoupper($dataItem['unit_case']) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                    $arrProductIdQty[$dataItem['product_id']] = $dataItem['qty']/ $dataItem['unit_qty'];
                } else {
                    $arrProductIdQty[$dataItem['product_id']] = $dataItem['qty'];
                }
            }
        }


        /*validate addition category rule*/
        foreach ($arrProductCartSession as $item) {
            $dataItem = $item->getData();
            if (isset($dataItem['is_addition']) && $dataItem['is_addition'] == 1) {
                $hasAdditional = true;
            }
        }
        $arrCategoryProductId = $this->helperCourse->arrCategoryIdQty($courseId);
        if (count($arrCategoryProductId) > 1) {
            $categoryId = $arrCategoryProductId[0];
            if ($categoryObj = $this->_categoryFactory->create()->load($categoryId)) {
                $categoryName = $categoryObj->getName();
            } else {
                $categoryName = '';
            }
            $qtyOfCategory = $arrCategoryProductId[1];
        } else {
            $qtyOfCategory = 0;
            $categoryName = '';
        }

        /*validate minimum qty profile cart*/
        $miniShoppingCartQty = $this->helperCourse->getMinimumQtyShoppingCart($courseId);
        $qty = $this->getQtyByRequestOrSession($arrParamProductQty, $arrProductCartSession, $arrParams);
        if ($qty == 0) {
            $objMessageManager->addError(__("Subscription profile must have at least one product."));
            return false;
        }

        /*validate maximum qty profile cart*/
        $maximumOrderQtyConfig = $this->getConfig(AdvancedInventoryStock::ADVANCED_INVENTORY_MAXIMUM_CART_STOCK);
        if ($maximumOrderQtyConfig > 0 && $totalQtyInFo > $maximumOrderQtyConfig) {
            $objMessageManager->addError(
                __(AdvancedInventoryStock::MORE_THAN_TOTAL_NUMBER_ITEM_ERROR_MESSAGE)
            );
            return false;
        }

        /*validate rule for subscription course*/
        $courseName = $objProfileCache->getCourseName();
        $errorCode = $this->helperCourse->validateProductOfCourse($courseId, $arrProductId, $qty, $arrProductIdQty, $objProfileCache->getData('order_times'));
        switch ($errorCode) {
            case Constant::ERROR_SUBSCRIPTION_COURSE_MUST_SELECT_SKU:
                $message = "You must select at least %1 quantity product(s) belong to \"%2\" category in %3";
                $objMessageManager->addError(__($message, $qtyOfCategory, $categoryName, $courseName));
                return false;
            case Constant::ERROR_SUBSCRIPTION_COURSE_MINIMUM_QTY:
                $objMessageManager->addError(
                    sprintf(
                        __("The total number of items in the shopping cart have at least %s quantity %s"),
                        $miniShoppingCartQty,
                        ''
                    )
                );
                return false;
            case Constant::ERROR_SUBSCRIPTION_COURSE_MUST_HAVE_QTY_CATEGORY:
                $message = "You must select at least %1 quantity product(s) belong to \"%2\" category in %3";
                $objMessageManager->addError(__($message, $qtyOfCategory, $categoryName, $courseName));
                return false;
        }

        return true;
    }

    /**
     * @param \Riki\Subscription\Model\Profile\Profile $objProfileCache
     * @return mixed
     */
    public function preparedProfileCartItemData($objProfileCache){

        $arrProductCartSession = $objProfileCache->getData(Constant::CACHE_PROFILE_PRODUCT_CART);

        $shippingAddressId = false;

        if (is_array($arrProductCartSession) && count($arrProductCartSession)) {
            foreach ($arrProductCartSession as $item) {
                if ($item->getData('shipping_address_id')) {
                    $shippingAddressId = $item->getData('shipping_address_id');
                    break;
                }
            }

            foreach ($arrProductCartSession as $index => $item) {
                $arrProductCartSession[$index]->setData('shipping_address_id', $shippingAddressId);
            }
        }

        return $arrProductCartSession;
    }

    /**
     * @param $profileId
     * @param $objProfile
     * @param \Riki\Subscription\Model\Profile\Profile $objProfileSession
     * @param $isBackend
     * @param $paymentMethod
     * @param \Magento\Framework\Message\ManagerInterface $objMessageManager
     * @param $arrParams
     * @return bool|mixed
     */
    public function confirmedAllChange($profileId, $objProfile, $objProfileSession, $isBackend, $paymentMethod, $objMessageManager, $arrParams){
        $hasDataChanged = $this->helperProfile->hasDataChanged($objProfile, $objProfileSession);
        if ($hasDataChanged) {
            $objMessageManager->addError(__('Exclusive access control error. Please try again.'));
            return $this->strRedirectWhenFail;
        }
        $arrAddress = $objProfileSession->getData('address');
        if (is_null($arrAddress)) {
            $objMessageManager->addError(__('Something went wrong, please reload page.'));
            return $this->strRedirectWhenFail;
        }
        $profileType = $objProfileSession->getData('profile_type');
        $objProfileSession->setData('trading_id', $objProfile->getTradingId());
        $this->profileRepository->save($objProfileSession, $profileType, $arrAddress, $isBackend?'BO':'FO');

        $arrParams['save_prederred'] = isset($arrParams['save_prederred']) ? $arrParams['save_prederred'] : '';
        if ($arrParams['save_prederred'] == '' && $objProfileSession->getData('paygent_save_prederred') != '') {
            $arrParams['save_prederred'] = $objProfileSession->getData('paygent_save_prederred');
        }

        if ($arrParams['save_prederred']) {
            $customer = $this->customerFactory->create()->load($objProfileSession->getData("customer_id"));

            $preferredMethod = $customer->getData('preferred_payment_method');
            $preferredMethodSelected = null;
            if ($preferredMethod) {
                $preferredMethodSelected = $preferredMethod;
            }

            if ($paymentMethod != $preferredMethodSelected) {
                //update new preferred method payment
                try {
                    $customer = $this->customerFactory->create();
                    $customerData = $customer->getDataModel();
                    $customerData->setId($objProfileSession->getCustomerId());
                    $customerData->setCustomAttribute('preferred_payment_method', $paymentMethod);
                    $customer->updateData($customerData);
                    $customerResource = $this->customerResourceFactory->create();
                    $customerResource->saveAttribute($customer, 'preferred_payment_method');
                } catch (\Exception $e) {
                    $objMessageManager->addError(__('We can\'t update your preferred payment method.'));
                    return $this->strRedirectWhenFail;
                }
            }
        }
        if (!$isBackend) {
            //get Trading Id - need to load from model again - In case if there are already redirect

            $tradingId = $objProfile->getTradingId();
            //validate card info for paygent

            $arrParams['new_paygent'] = ((isset($arrParams['payment_method'])) && $arrParams['payment_method'] == 'new_paygent')
                ? 1 : '';
            if ($arrParams['new_paygent'] == '' && $objProfileSession->getData('new_paygent') != '') {
                $arrParams['new_paygent'] = $objProfileSession->getData('new_paygent');
            }

            // 10.11.1 - remove session final step
            $objProfileSession->unsetData('address');
            $objProfileSession->unsetData('profile_type');
            $objProfileSession->unsetData('new_paygent');

            if (($paymentMethod == \Bluecom\Paygent\Model\Paygent::CODE && !$tradingId) ||
                ($paymentMethod == \Bluecom\Paygent\Model\Paygent::CODE && $arrParams['new_paygent'])
            ) {
                $isHanpukai = false;
                if ((int)$objProfileSession->getData('hanpukai_qty') > 0) {
                    $isHanpukai = true;
                }
                $this->paygentModel->validateCard($profileId, $isHanpukai);
            }

            $redirectPath = $this->_customerSession->getData('verify_url', true);

            if ($redirectPath) {
                $objMessageManager->addSuccess(__("Update profile successfully!"));
                return $redirectPath;
            }
        }

        /** Redirect back to view page*/
        return true;
    }

    /**
     * @param $arrAllProductCatIdByAddrByDL
     * @param $arrProductCartCache
     * @param $objMessageManager
     * @param $arrParams
     * @return bool
     */
    public function saveProductCartInfo(
        $arrAllProductCatIdByAddrByDL,
        $arrProductCartCache,
        $objMessageManager,
        $arrParams
    ) {
        $newShippingAddressId = false;

        foreach ($arrAllProductCatIdByAddrByDL as $addressId => $arrPCartByDL) {
            foreach ($arrPCartByDL as $deliveryType => $arrPCartId) {
                foreach ($arrPCartId as $i => $pcartId) {
                    /** @var \Magento\Framework\DataObject $objProductCat */
                    $objProductCat = isset($arrProductCartCache[$pcartId])?$arrProductCartCache[$pcartId]:null;

                    if (!$newShippingAddressId &&
                        isset($arrParams['address'][$addressId][$deliveryType])
                    ) {
                        $newShippingAddressId = $arrParams['address'][$addressId][$deliveryType];
                    }

                    if (empty($objProductCat) || empty($objProductCat->getData("cart_id"))) {
                        continue;
                    }

                    /** Collect information */
                    $productCatQty = $objProductCat->getData("qty");
                    if (isset($arrParams["product_qty"]) && isset($arrParams["product_qty"][$pcartId])) {
                        if (isset($arrParams["product_qty"])) {
                            $productCatQty = $arrParams["product_qty"][$pcartId];
                        } else {
                            $productCatQty = $objProductCat->getData("qty");
                        }
                    }

                    if (isset($arrParams["product_qty_case"]) && isset($arrParams["product_qty_case"][$pcartId])) {
                        $productCatQty = $arrParams["product_qty_case"][$pcartId];
                        $productCatUnitQty = $arrParams["product_unit_qty"][$pcartId];
                        $productCatQty = $productCatQty * $productCatUnitQty;
                    }

                    $deliveryDate = isset($arrParams['next_delivery']) ?   $arrParams['next_delivery'][$addressId][$deliveryType] : $objProductCat->getData("delivery_date");
                    $deliveryTimeSlot = isset($arrParams['time_slot'])
                    && isset($arrParams['time_slot'][$addressId])
                    && isset($arrParams['time_slot'][$addressId][$deliveryType]) ? $arrParams['time_slot'][$addressId][$deliveryType] : $objProductCat->getData("delivery_time_slot");

                    $productGift = (isset($arrParams["gift"]) && isset($arrParams["gift"][$addressId][$pcartId])) ? $arrParams["gift"][$addressId][$pcartId] : $objProductCat->getData("gw_id");

                    $productMessage = (isset($arrParams["giftmessage"]) && isset($arrParams["giftmessage"][$addressId][$pcartId])) ? $arrParams["giftmessage"][$addressId][$pcartId] : '';

                    $productmessageId = (isset($arrParams["giftmessageid"]) && isset($arrParams["giftmessageid"][$addressId][$pcartId])
                        && $arrParams["giftmessageid"][$addressId][$pcartId] != ''
                        && $arrParams["giftmessageid"][$addressId][$pcartId] != null
                    )
                        ? $arrParams["giftmessageid"][$addressId][$pcartId] : $objProductCat->getData("gift_message_id");

                    if ($productMessage != '') {
                        $giftMessage = $this->helperProfile->saveMessage($productmessageId, $productMessage);
                    }

                    $productmessageId = (isset($giftMessage) && $giftMessage != '' && $giftMessage != null && $giftMessage != false) ?  $giftMessage : $objProductCat->getData("gift_message_id");

                    /*skip seasonal product*/

                    $isSkip = (isset($arrParams["is_skip_productcat"]) and isset($arrParams["is_skip_productcat"][$pcartId]) )? $arrParams["is_skip_productcat"][$pcartId] : null;
                    if ($isSkip) {
                        $skipFrom = (isset($arrParams["skip_from_productcat"]) and isset($arrParams["skip_from_productcat"][$pcartId])) ? $arrParams["skip_from_productcat"][$pcartId] : $objProductCat->getData("skip_from");
                        $skipTo = (isset($arrParams["skip_to_productcat"]) and isset($arrParams["skip_to_productcat"][$pcartId])) ? $arrParams["skip_to_productcat"][$pcartId] : $objProductCat->getData("skip_to");
                    }
                    $isAddition = (isset($arrParams['is_addition']) and isset($arrParams["is_addition"][$pcartId]))?$arrParams["is_addition"][$pcartId]:$objProductCat->getData("is_addition");


                    $objProductCat->setData('delivery_date', $deliveryDate);
                    $objProductCat->setData('delivery_time_slot', $deliveryTimeSlot);
                    if (!(isset($arrParams['is_added']) and in_array($objProductCat->getData('product_id'), explode(',', $arrParams['is_added'])))) {
                        $objProductCat->setData('qty', $productCatQty);
                    }
                    $objProductCat->setData('gw_id', $productGift);
                    $objProductCat->setData('gift_message_id', $productmessageId);
                    if ($isSkip) {
                        $objProductCat->setData('is_skip_seasonal', $isSkip);
                        $objProductCat->setData('skip_from', $skipFrom);
                        $objProductCat->setData('skip_to', $skipTo);
                    } else {
                        $objProductCat->setData('is_skip_seasonal', null);
                        $objProductCat->setData('skip_from', null);
                        $objProductCat->setData('skip_to', null);
                    }
                    $objProductCat->setData('is_addition', $isAddition);
                    $objProductCat->setData("updated_at", (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
                }
            }
        }

        if ($newShippingAddressId) {
            foreach ($arrProductCartCache as $pcartId => $objProductCat) {
                $objProductCat->setData('shipping_address_id', $newShippingAddressId);
            }
        }

        return true;
    }

    /**
     * get Qty of product cart
     *
     * @param $arrParamProductQty
     * @param $arrProductCartCache
     * @return int
     */
    public function getQtyByRequestOrSession($arrParamProductQty, $arrProductCartCache, $arrParams = null) {
        $qty = 0;
        if (! is_null($arrParamProductQty) and is_array($arrParamProductQty)) {
            foreach ($arrParamProductQty as $pcartId => $_qty) {
                if (!is_null($arrParams) and isset($arrParams['product_qty_case']) and isset($arrParams['product_qty_case'][$pcartId])) {
                    if (isset($arrParams['product_unit_qty']) and isset($arrParams['product_unit_qty'][$pcartId])) {
                        $_qty = $_qty/$arrParams['product_unit_qty'][$pcartId];
                    }
                }
                $qty += $_qty;
            }
            return $qty;
        }
        foreach ($arrProductCartCache as $pcartId => $obj) {
            $qty += $obj->getData("qty");
        }
        return $qty;
    }

    /**
     * Get Array product cart ID
     *
     * @param $arrProductCart
     * @return array
     */
    public function getArrProductId($arrProductCart) {
        if (empty($arrProductCart)) {
            return [];
        }
        $arrProductId = [];
        foreach ($arrProductCart as $item) {
            if ($item['parent_item_id']) {
                continue;
            }

            $arrProductId[] = $item['product_id'];
        }
        return $arrProductId;
    }

    /**
     * remove duplicate and merge product
     *
     * @param $profileProductCart
     * @return array
     */
    public function removeDuplicateAndMerge($profileProductCart)
    {
        $arrProductId = [];

        foreach ($profileProductCart as $objProductCart) {
            $arrProductId[] = $objProductCart->getData('product_id');
        }

        $arrMap = $this->helperDelivery->getMapBetweenProductIdDeliveryType($arrProductId);

        $arrDuplicateId = [];

        foreach ($profileProductCart as $_pcid => $objProductCart) {
                $productId = $objProductCart->getData("product_id");
                $addressId = $objProductCart->getData("shipping_address_id");
                $deliveryType = $arrMap[$productId];

                if (isset($arrDuplicateId[$productId . '_' . $addressId . '_' . $deliveryType])) {
                    array_unshift($arrDuplicateId[$productId . '_' . $addressId . '_' . $deliveryType], $_pcid);
                } else {
                    $arrDuplicateId[$productId . '_' . $addressId . '_' . $deliveryType][] = $_pcid;
                }
        }

        $arrNeedRemovePcId = [];
        foreach ($arrDuplicateId as $groupKey => $arrPcId) {
            $objProductCart = $profileProductCart[$arrPcId[0]];
            array_shift($arrPcId);
            $qty = $objProductCart->getData('qty');

            if (!empty($arrPcId)) {
                foreach ($arrPcId as $_pcid) {
                    $qty += $profileProductCart[$_pcid]->getData("qty");
                    $arrNeedRemovePcId[] = $_pcid;
                    unset($profileProductCart[$_pcid]);
                }
            }

            $objProductCart->setData("qty", $qty);
        }

        return $profileProductCart;
    }

    /**
     * Check if change address type
     *
     * @param $arrParams
     * @param $profileSession
     * @return bool
     */
    public function checkChangeAddressType($arrParams, $profileSession){
        $customerId = $profileSession->getData('customer_id');
        $addressChangeTo =  [];
        if ($customerId) {
            if (isset($arrParams['payment_method']) &&
                $arrParams['payment_method'] == \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_COD
            ) {
                $isAmbassador = $this->checkCustomerAmbassador($customerId);
                if (isset($arrParams['address'])) {
                    foreach ($arrParams['address'] as $oldAddress => $newAddress) {
                        foreach ($newAddress as $key => $value) {
                            $addressChangeTo[] = $value;
                        }
                    }
                }
                foreach ($addressChangeTo as $addressId) {
                    $addressType = $this->helperProfile->getCustomerAddressType($addressId);
                    if ($addressType) {
                        if ($addressType == \Riki\Customer\Model\Address\AddressType::SHIPPING) {
                            return false;
                        } elseif ($addressType == \Riki\Customer\Model\Address\AddressType::OFFICE) {
                            if (!$isAmbassador) {
                                return false;
                            }
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }
    /**
     * Check customer is Ambassador.
     *
     * @param $customerId
     * @return bool
     */
    public function checkCustomerAmbassador($customerId){
        $customerModel = $this->customerFactory->create()->load($customerId);
        if ($customerModel->getId()) {
            $checkAmbassador = $customerModel->getMembership();
            if (strpos($checkAmbassador, '3') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check item DM only in cart
     *
     * @param $arrProductCartCache
     * @return bool
     */
    public function checkDmOnly($action, $arrProductCartCache){
        $objMessageManager = $action->getMessageManager();
        $onlyDm = $this->_calculateDeliveryDateHelper->checkProductDm($arrProductCartCache);

        if ($onlyDm) {
            $objMessageManager->addError(__("Please change payment method from COD to other Payment"));
            return false;
        }
    }

    /**
     * Get profile helper data
     *
     * @return \Riki\Subscription\Helper\Profile\Data
     */
    public function getProfileHelperData()
    {
        return $this->helperProfile;
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
     * validate condition stock point before save
     * @param $objProfileCache
     * @param $productCartCache
     * @param $arrParams
     * @return \Magento\Framework\Phrase|null|string
     */
    public function validateStockPoint($objProfileCache, $productCartCache, $arrParams = [])
    {
        $message = null;
        if ($objProfileCache) {
            $rikiStockPoint = $objProfileCache->getData('riki_stock_point_id');
            $existStockPoint = $objProfileCache->getData('stock_point_profile_bucket_id');
            if ((int)$existStockPoint >0 || $rikiStockPoint) {
                $isValid = true;

                if (isset($arrParams['payment_method']) &&
                    !(
                        $arrParams['payment_method'] == \Bluecom\Paygent\Model\Paygent::CODE ||
                        $arrParams['payment_method'] == \Bluecom\Paygent\Model\Paygent::CODE_NEW
                    )
                ) {
                    $isValid = false;
                }
                /**
                 * Check all allow stock point
                 */
                $errorStockPoint = $this->_validateAllProductAllowStockPoint($productCartCache, $objProfileCache);
                if (!empty($errorStockPoint)) {
                    $text1 = "Stock Point is not allowed for these products [%s].";
                    $text2 = "Please remove them from cart before you choose to deliver with Stock Point.";
                    return sprintf(__($text1.$text2), implode(',', $errorStockPoint));
                }

                /**
                 * All products are in stock on Hitachi
                 */
                if (!$this->_validateProductInventoryForStockPoint($existStockPoint)) {
                    $isValid = false;
                }

                /**
                 * Check payment method = paygent
                 */
                if ($objProfileCache->getData('payment_method') != \Bluecom\Paygent\Model\Paygent::CODE) {
                    $isValid = false;
                }

                /**
                 * Check delivery type address
                 */
                $deliveryType = $this->productDeliveryTypes;
                if (!$this->validateStockPointProduct->validateDeliveryTypeAddress($deliveryType)) {
                    $isValid = false;
                }

                if (!$isValid) {
                    return __("Sorry, MACHI ECO flights can not be used with the items you purchase / payment method.");
                }
            }
        }
        return $message;
    }

    /**
     * @param array $productCartCache
     * @return array
     */
    public function _validateAllProductAllowStockPoint(array $productCartCache, $objProfileCache = null )
    {
        $dataErrorProducts = [];
        $productIds = $this->getArrProductId($productCartCache);
        $query = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $productIds, 'in')
            ->create();
        $allCartProductCollection = $this->_productRepository->getList($query);
        if ($allCartProductCollection->getTotalCount()) {
            $allCartProductQty = $this->validateStockPointProduct->convertDataProductCartSession($productCartCache);
            $newlyAddedCartProducts = $this->_getNewlyAddedProducts($productCartCache);
            /** need to do the mass refactor here */
            foreach ($allCartProductCollection->getItems() as $product) {
                
                /** if requested product is not avaialble in session, consider it as out-of-stock */
                if (!isset($allCartProductQty[$product->getId()])) {
                    $dataErrorProducts[$product->getName()] = $product->getName();
                    continue;
                }
                
                /**
                 * Get all product doesn't enable allow stock point. If there are error products, error message
                 * will be shown on UI.
                 */
                if(!$this->validateStockPointProduct->checkProductAllowStockPoint(
                    $objProfileCache,
                    $product,
                    [ 
                        $product->getId() => [
                          "product" => $product,
                          "qty"     => $allCartProductQty[$product->getId()]
                        ]
                    ]
                )
                && !$product->getData('parent_item_id') // if is single product
                ) {
                    $dataErrorProducts[$product->getName()] = $product->getName();
                    continue;
                }

                /**
                 * Check product exist on cart session.
                 */
                if (isset($allCartProductQty[$product->getId()])) {
                    $this->allCartProductItems[$product->getId()]['product'] = $product;
                    $this->allCartProductItems[$product->getId()]['qty'] = $allCartProductQty[$product->getId()];
                }

                /**
                 * Filter newly added products for profile exist stock point.
                 */
                if (isset($newlyAddedCartProducts[$product->getId()])) {
                    $this->newlyAddedProducts[$product->getId()]['product'] = $product;
                    $this->newlyAddedProducts[$product->getId()]['qty'] = $allCartProductQty[$product->getId()];
                }

                /**
                 * Collecting product's delivery type.
                 * Only check parent product.
                 */
                if (!$product->getData('parent_item_id')) {
                    $deliveryType = $product->getData('delivery_type');
                    $this->productDeliveryTypes[$deliveryType] = $deliveryType;
                }
            }
        }

        return $dataErrorProducts;
    }

    public function getProductDeliveryTypes(){
        return $this->productDeliveryTypes;
    }

    /**
     * Get product new
     *
     * @param $productCartItemsCache
     * @return array
     */
    private function _getNewlyAddedProducts(array $productCartItemsCache)
    {
        $items = [];
        foreach ($productCartItemsCache as $key => $product) {
            if (!(int)$key) {
                $items[$product->getProductId()] = $product;
            }
        }
        return $items;
    }

    /**
     * Validate product for product stock point
     *
     * @param $existStockPoint
     * @return bool
     */
    public function _validateProductInventoryForStockPoint($existStockPoint)
    {
        /**
         * Case 1 : profile exist stock point
         */
        if ($existStockPoint) {
            /**
             * If $this->newlyAddedProducts is null as update profile ==> not validate product
             */
            if (!empty($this->newlyAddedProducts)) {
                return $this->validateStockPointProduct->checkAllProductInStockWareHouse($this->newlyAddedProducts);
            }
        } elseif (!empty($this->allCartProductItems)) {
            /**
             * Case 2 : profile change to normal => stock point
             */
            return $this->validateStockPointProduct->checkAllProductInStockWareHouse($this->allCartProductItems);
        }
        return true;
    }
}
