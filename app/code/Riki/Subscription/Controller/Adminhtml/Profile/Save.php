<?php
namespace Riki\Subscription\Controller\Adminhtml\Profile;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Riki\Subscription\Model\Constant;
use Riki\Subscription\Model\Profile\ProfileFactory;
use Riki\Subscription\Model\ProductCart\ProductCartFactory;
use Riki\Subscription\Model\Profile\Profile;
use Riki\Subscription\Model\Version\VersionFactory;
use Riki\AdvancedInventory\Model\Stock as AdvancedInventoryStock;

class Save extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    protected $_profileFactory;

    protected $_versionFactory;

    protected $_productCartFactory;

    protected $_helperCourse;

    protected $_helperProfile;

    protected $_controllerHelper;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_sessionManager;

    protected $categoryRepository;

    protected $customer;

    protected $customerAddress;

    protected $logger;

    protected $helperData;

    protected $appState;

    protected $frequency;

    protected $profileModel;

    protected $calculateDeliveryDate;

    protected $emailProfile;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subOrderHelper;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    protected $profileCache;

    /**
     * Save constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param ProfileFactory $profileFactory
     * @param ProductCartFactory $productCartFactory
     * @param VersionFactory $versionFactory
     * @param \Riki\SubscriptionCourse\Helper\Data $helperCourse
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Riki\Subscription\Helper\Profile\Controller\Save $controllerHelper
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Customer\Model\Address $customerAddress
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Subscription\Helper\Data $helperData
     * @param \Riki\Subscription\Model\Frequency\Frequency $frequency
     * @param Profile $profileModel
     * @param \Riki\Subscription\Helper\CalculateDeliveryDate $calculateDeliveryDate
     * @param \Riki\Subscription\Helper\Profile\Email $emailProfile
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCache
     * @param \Riki\Subscription\Helper\Order $subOrderHelper
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        ProfileFactory $profileFactory,
        ProductCartFactory $productCartFactory,
        VersionFactory $versionFactory,
        \Riki\SubscriptionCourse\Helper\Data $helperCourse,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\Subscription\Helper\Profile\Controller\Save $controllerHelper,
        \Magento\Framework\App\State $state,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Customer\Model\Address $customerAddress,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Helper\Data $helperData,
        \Riki\Subscription\Model\Frequency\Frequency $frequency,
        Profile $profileModel,
        \Riki\Subscription\Helper\CalculateDeliveryDate $calculateDeliveryDate,
        \Riki\Subscription\Helper\Profile\Email $emailProfile,
        \Riki\Subscription\Helper\Order $subOrderHelper,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCache
    ) {
        $this->_scopeConfig = $scopeConfigInterface;
        $this->_coreRegistry = $coreRegistry;
        $this->_profileFactory = $profileFactory;
        $this->_productCartFactory = $productCartFactory;
        $this->_versionFactory = $versionFactory;
        $this->_helperCourse = $helperCourse;
        $this->_helperProfile = $helperProfile;
        $this->_controllerHelper = $controllerHelper;
        $this->appState = $state;
        $this->categoryRepository = $categoryRepository;
        $this->customer = $customer;
        $this->customerAddress = $customerAddress;
        $this->logger = $logger;
        $this->helperData  = $helperData;
        $this->frequency = $frequency;
        $this->profileModel = $profileModel;
        $this->calculateDeliveryDate = $calculateDeliveryDate;
        $this->emailProfile =  $emailProfile;
        $this->subOrderHelper = $subOrderHelper;
        $this->subscriptionValidator = $subscriptionValidator;
        $this->profileCache = $profileCache;
        parent::__construct($context);
    }

    public function getStrRedirectWhenFail()
    {
        return '*/*/edit';
    }

    public function getStrRedirectWhenConfirm()
    {
        return '*/*/edit';
    }

    public function getStrRedirectWhenProfileNotExists()
    {
        return '*/*/edit';
    }

    /**
     * @return \Magento\Framework\Data\Form\FormKey\Validator
     */
    public function getFormkeyValidator()
    {
        return $this->_formKeyValidator;
    }

    /**
     * @return \Magento\Framework\Message\ManagerInterface
     */
    public function getMessageManager()
    {
        return $this->messageManager;
    }

    /**
     * @return bool|mixed
     * @throws LocalizedException
     * @throws \Zend_Serializer_Exception
     */
    public function getProfileCache()
    {
        $profileId = $this->_request->getParam('profile_id');
        return $this->profileCache->getProfileDataCache($profileId);
    }

    /**
     * Create gift registry action
     *
     * @return void|ResponseInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        // 1
        $arrParams = $this->getRequest()->getPostValue();

        $fromDelete = isset($arrParams['is_deleted'])?:false;
        $fromAddNewProduct = isset($arrParams['is_added'])?:false;


        $objMessageManager = $this->getMessageManager();
        $profileId = $this->getRequest()->getParam('profile_id');
        $profileIdReturn = $this->_helperProfile->getProfileOriginFromTmp($profileId);

        $isGenerateOrderPressed = isset($arrParams['save_profile']) && $arrParams['save_profile'] === 'generate_order_confirm';
        $isBackBtnPressed = isset($arrParams['save_profile']) && $arrParams['save_profile'] === 'back';
        $isConfirmPressed = isset($arrParams['save_profile']) && $arrParams['save_profile'] == 'confirm';

        $objProfileCache = $this->getProfileCache();

        if (!$objProfileCache) {
            $objMessageManager->addError(__('Something went wrong, please reload page.'));
            $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
            return;
        }
        $isValid = $this->validateInfo($objProfileCache, $this, $profileId, $arrParams);

        $arrProductCartCache = !empty($objProfileCache)? $this->preparedProfileCartItemData($objProfileCache) : [];

        /** validate stock point */
        $messageError = $this->_controllerHelper->validateStockPoint(
            $objProfileCache,
            $arrProductCartCache,
            $arrParams
        );
        if (!empty($messageError)) {
            $objMessageManager->addError($messageError);
            $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
            return;
        }

        if ($isValid === false and ! ($fromDelete || $fromAddNewProduct)) {
            $this->_redirect($this->_request->getServer('HTTP_REFERER'));
            return;
        }

        if (($fromAddNewProduct || $fromDelete) and $arrProductCartCache === false) {
            $arrProductCartCache = [];
        }
        if (is_array($arrProductCartCache) and empty($arrProductCartCache) and ! ($fromDelete || $fromAddNewProduct)) {
            $objMessageManager->addError(__("Subscription profile must have at least one product."));
            $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
            $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
            return;
        } else {
            // if only SPOT exist in profile, next delivery the profile will invalid
            if (!($fromDelete || $fromAddNewProduct)) {
                $productSpot = $this->_helperProfile->checkDeleteProductSpot($arrProductCartCache);
                if ($productSpot) {
                    $objMessageManager->addError(__("Profile has only SPOT item. You must add at least one subscription item"));
                    $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
                    $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
                    return;
                }
            }
        }

        $objProfileHelper = $this->_helperProfile;
        // 10
        try {
            // 10.1
            $objProfileHelperCommon = $this->helperData;

            // 10.2
            if ($isBackBtnPressed) {
                $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
                $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
                return;
            }

            // 10.3
            /*validate minimum order qty and must have SKU*/
            $courseId = $objProfileCache->getData('course_id');
            $objCourseHelper = $this->_helperCourse;

            // 10.4
            $arrProductId       = $this->_controllerHelper->getArrProductId($arrProductCartCache);
            $arrProductIdQty = [];
            $hasMainProduct = false;
            $hasAdditional = false;
            $totalQtyInFo = 0;
            $arrParamProductQty =  isset($arrParams['product_qty']) ? $arrParams['product_qty'] : null;
            // in admin array param post qty of product case not include in arr product_qty.
            $arrParamProductQtyCase = isset($arrParams['product_qty_case']) ? $arrParams['product_qty_case'] : null;
            foreach ($arrProductCartCache as $item) {
                $dataItem = $item->getData();
                $dataItem['is_addition'] = isset($dataItem['is_addition'])?$dataItem['is_addition']:0;

                if ($dataItem['is_addition'] == 0 && $hasMainProduct == false) {
                    $hasMainProduct = true;
                }

                if (array_key_exists('qty', $dataItem) && array_key_exists('unit_case', $dataItem)
                    && array_key_exists('unit_qty', $dataItem)) {
                    if (strtoupper($dataItem['unit_case']) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        if (is_array($arrParamProductQtyCase)
                        && array_key_exists($dataItem['cart_id'], $arrParamProductQtyCase)) {
                            $totalQtyInFo = $totalQtyInFo + $arrParamProductQtyCase[$dataItem['cart_id']];
                            $arrProductIdQty[$dataItem['product_id']] = $arrParamProductQtyCase[$dataItem['cart_id']];
                        }
                    } else {
                        if (is_array($arrParamProductQty) && array_key_exists($dataItem['cart_id'], $arrParamProductQty)) {
                            $totalQtyInFo = $totalQtyInFo + $arrParamProductQty[$dataItem['cart_id']];
                            $arrProductIdQty[$dataItem['product_id']] = $arrParamProductQty[$dataItem['cart_id']];
                        }
                    }
                }
            }
            foreach ($arrProductCartCache as $item) {
                $dataItem = $item->getData();
                if (isset($dataItem['is_addition']) && $dataItem['is_addition'] == 1) {
                    $hasAdditional = true;
                }
            }

            $arrCategoryProductId = $objCourseHelper->arrCategoryIdQty($courseId);
            if (count($arrCategoryProductId) > 1) {
                $categoryId = $arrCategoryProductId[0];
                if ($categoryObj = $this->categoryRepository->get($categoryId)) {
                    $categoryName = $categoryObj->getName();
                } else {
                    $categoryName = '';
                }
                $qtyOfCategory = $arrCategoryProductId[1];
            } else {
                $qtyOfCategory = 0;
                $categoryName = '';
            }
            $miniShoppingCartQty = $objCourseHelper->getMinimumQtyShoppingCart($courseId);
            $arrParamProductQty =  isset($arrParams['product_qty']) ? $arrParams['product_qty'] : null;
            if (isset($arrParams['product_qty_case'])) {
                if (is_array($arrParamProductQty)) {
                    $arrParamProductQty = array_merge($arrParamProductQty, $arrParams['product_qty_case']);
                } else {
                    $arrParamProductQty = $arrParams['product_qty_case'];
                }
            }
            $qty = $this->_controllerHelper->getQtyByRequestOrSession($arrParamProductQty, $arrProductCartCache);
            if ($qty == 0 and !$fromDelete) {
                $objMessageManager->addError(__("Subscription profile must have at least one product."));
                $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
                $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
                return;
            }
            if ($objProfileCache->getData('disengagement_date') &&
                $objProfileCache->getData('disengagement_reason') &&
                $objProfileCache->getData('disengagement_user')
            ) {
                $errorCode = 0;
            } else {
                $errorCode = $objCourseHelper->validateProductOfCourse($courseId, $arrProductId, $qty, $arrProductIdQty,
                    $objProfileCache->getData('order_times'));
            }
            $courseName = $objProfileCache->getCourseName();
            switch ($errorCode) {
                case 3:
                    $objMessageManager->addError(__("You must select at least %1 quantity product(s) belong to \"%2\" category in %3", $qtyOfCategory, $categoryName, $courseName));
                    $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
                    $result = false;
                    break;
                case 4:
                    $objMessageManager->addError(
                        sprintf(
                            __("The total number of items in the shopping cart have at least %s quantity"),
                            $miniShoppingCartQty
                        )
                    );
                    $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
                    $result = false;
                    break;
                case 5:
                    $objMessageManager->addError(__("You must select at least %1 quantity product(s) belong to \"%2\" category in %3", $qtyOfCategory, $categoryName, $courseName));
                    $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
                    $result = false;
                    break;
                default:
                    $result = true;
                // Do nothing
            }
            $maximumOrderQtyConfig = $this->getConfig(AdvancedInventoryStock::ADVANCED_INVENTORY_MAXIMUM_CART_STOCK);
            if ($maximumOrderQtyConfig > 0 && $totalQtyInFo > $maximumOrderQtyConfig) {
                $objMessageManager->addError(
                    __(AdvancedInventoryStock::MORE_THAN_TOTAL_NUMBER_ITEM_ERROR_MESSAGE),
                    $qtyOfCategory,
                    $categoryName
                );
                $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '0');
                $result = false;
            }
            if (!$result and !($fromDelete || $fromAddNewProduct)) {
                $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
                return;
            }

            // 10.5
            $isBackend = 0;
            if ($this->appState->getAreaCode() ==="adminhtml") {
                $isBackend = 1;
            }

            if (!$isBackend) {
                $customerId = $objProfileCache->getData("customer_id");
                if (! $objProfileHelper->isHaveViewProfilePermission($customerId, $profileId)) {
                    $objMessageManager->addError(__("Access denied"));
                    $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
                    return;
                }
            }
            // 10.6 - Prepare save profile first
            $frequencyId = isset($arrParams['frequency_id']) ? $arrParams['frequency_id'] : $objProfileHelperCommon->getFrequencyIdByUnitAndInterval($objProfileCache->getData("frequency_unit"), $objProfileCache->getData("frequency_interval"));
            $paymentMethod = isset($arrParams['payment_method']) ? $arrParams['payment_method'] : $objProfileCache->getData("payment_method");

            $arrAllProductCatIdByAddrByDL = isset($arrParams['productcat_id']) ? $arrParams['productcat_id'] : [];
            $isSkipNextDelivery = isset($arrParams['skip_next_delivery']) ?  $arrParams['skip_next_delivery'] == 'on' : $objProfileCache->getData("skip_next_delivery");
            $earnPoint = isset($arrParams['earn_point_on_order']) ?  $arrParams['earn_point_on_order']  : $objProfileCache->getData("earn_point_on_order");

            // 2.3.2 - editable to new paygent
            $isNewPaygent = isset($arrParams['new_paygent']) ? $arrParams['new_paygent'] == 1 : $objProfileCache->getData('new_paygent');

            // 10.7
            $objFrequency = $this->frequency;
            $objFrequency->load($frequencyId);
            if (empty($objFrequency) || empty($objFrequency->getId())) {
                $objMessageManager->addError(__("Please choice frequency"));
                $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
                return;
            }


            // 10.8 Start set information
            $objProfileCache->setData("frequency_unit", $objFrequency->getData("frequency_unit"));
            $objProfileCache->setData("frequency_interval", $objFrequency->getData("frequency_interval"));
            $objProfileCache->setData("payment_method", $paymentMethod);
            $objProfileCache->setData("skip_next_delivery", $isSkipNextDelivery);
            $objProfileCache->setData("earn_point_on_order", $earnPoint);
            $objProfileCache->setData('updated_at', (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
            $objProfileCache->setData('new_paygent', $isNewPaygent);

            $objProfileCache->setData('coupon_code', implode(',', $objProfileCache->getData('appliedCoupon')));

            // 10.9
            if (empty($arrProductCartCache)) {
                $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
                return;
            }
            $result = $this->_controllerHelper->saveProductCartInfo($arrAllProductCatIdByAddrByDL, $arrProductCartCache, $objMessageManager, $arrParams);
            if ($result == false) {
                $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
                return;
            }
            // 10.10
            $arrProductCartCache = $this->_controllerHelper->removeDuplicateAndMerge($arrProductCartCache);
            $objProfileCache->setData(Constant::CACHE_PROFILE_PRODUCT_CART, $arrProductCartCache);

           // check Dm product Only and not seclect COD
            if (!empty($arrProductCartCache) && $paymentMethod == \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_COD
            ) {
                $dmOnly = $this->checkDmOnly($arrProductCartCache);
                if ($dmOnly) {
                    $objMessageManager->addError(__("Please change payment method from COD to other Payment"));
                    $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
                    return ;
                }
            }
            $this->profileCache->save($objProfileCache);
            // 10.11
            if ($isConfirmPressed) {
                $objProfile  = $objProfileHelper->load($profileId);
                $subscriptionCourse = $this->subOrderHelper->loadCourse($objProfileCache->getData('course_id'));
                $validateResults = $this->subOrderHelper->validateSimulateOrderAmountRestriction(
                    $subscriptionCourse,
                    $objProfileCache
                );
                if (!$validateResults['status']) {
                    $objMessageManager->addError($validateResults['message']);
                    $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
                    return;
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

                    $objMessageManager->addError($message);
                    $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
                    return;
                }

                $redirectResult = $this->_controllerHelper->confirmedAllChange($profileId, $objProfile, $objProfileCache, $isBackend, $paymentMethod, $objMessageManager, $arrParams);
                if ($redirectResult === true) {
                    /** clear cache when update successfully */
                    $this->profileCache->removeCache($profileId);

                    $objMessageManager->addSuccess(__("Update profile successfully!"));
                    $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
                    return;
                } else {
                    $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
                    return;
                }
            }

            /**
             * 1. Save profile
             * 2. Call ajax to generate order
             */
            if ($isGenerateOrderPressed) {

                $objProfile  = $objProfileHelper->load($profileId);
                $redirectResult = $this->_controllerHelper->confirmedAllChange($profileId, $objProfile, $objProfileCache, $isBackend, $paymentMethod, $objMessageManager, $arrParams);

                if ($redirectResult === true) {
                    /** clear cache when update successfully */
                    $this->profileCache->removeCache($profileId);
                    return $this->_forward('create', 'order');
                } else {
                    $this->_redirect($redirectResult);
                    return;
                }
            }
        } catch (\Exception $e) {
            $objMessageManager->addError($e->getMessage());
            $this->logger->critical($e);
        }

        $this->_redirect('profile/profile/edit', ['id' => $profileIdReturn]);
    }

    /**
     * Validate info before save profile
     *
     * @param \Riki\Subscription\Model\Profile\Profile $objProfileCache
     * @param \Riki\Subscription\Controller\Profile\Save $action
     * @param $profile_id
     * @param $arrParams
     * @return bool
     */
    public function validateInfo($objProfileCache, $action, $profileId, $arrParams){
        $objMessageManager = $action->getMessageManager();
        $objFormKeyValidator = $action->getFormkeyValidator();
        $isConfirmPressed = isset($arrParams['save_profile']) && $arrParams['save_profile'] === 'confirm';
        $isCreateOrderPressed = isset($arrParams['save_profile']) && $arrParams['save_profile'] === 'generate_order_confirm';
        if (empty($objProfileCache)) {
            $objMessageManager->addError(__('Something went wrong, please reload page.'));
            return false;
        }
        if (!$this->checkChangeAddressType($arrParams, $objProfileCache)) {
            $objMessageManager->addError(__('Please change payment method from COD to Paygent to update all changes.'));
            return false;
        }

        // 6
        if (!($profileId)) {
            return false;
        }

        // 7
        // Check profile is exists
        $objProfile  = $this->_helperProfile->load($profileId);

        // 8
        if (!($objProfile && $objProfile->getId())) {
            $objMessageManager->addError(__('Cannot found this profile'));
            return false;
        }

        if (! $objFormKeyValidator->validate($action->getRequest())) {
            return false;
        }

        if (! $action->getRequest()->isPost() || empty($arrParams)) {
            return false;
        }

        if ($isCreateOrderPressed) {
            $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '1');
            $objProfileCache->setData(Constant::CACHE_BTN_CREATE_ORDER_PRESSED, '1');
        }

        if ($isConfirmPressed || $isCreateOrderPressed) {
            $arrParams['save_prederred'] = isset($arrParams['save_prederred']) ? $arrParams['save_prederred'] : '';
            $objProfileCache->setData('paygent_save_prederred', $arrParams['save_prederred']);

            $arrParams['new_paygent'] = isset($arrParams['new_paygent']) && $arrParams['new_paygent'] == 1 ? $arrParams['new_paygent'] : '';
            $objProfileCache->setData('new_paygent', $arrParams['new_paygent']);

            $objProfileCache->setData('address', isset($arrParams['address']) ? $arrParams['address'] : null);
            $objProfileCache->setData('profile_type', isset($arrParams['profile_type'])? $arrParams['profile_type'] : 'type_2');

            $arrParams['skip_next_delivery'] = isset($arrParams['skip_next_delivery']) ? $arrParams['skip_next_delivery'] : '';
            $objProfileCache->setData('skip_next_delivery', $arrParams['skip_next_delivery'] == 'on');

            $arrParams['earn_point_on_order'] = isset($arrParams['earn_point_on_order']) ? $arrParams['earn_point_on_order'] : '';
            $objProfileCache->setData('earn_point_on_order', $arrParams['earn_point_on_order']);
        }

        // 9
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
        if ($isConfirmPressed) {
            $objProfileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, '1');
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
     * Check if change address type
     *
     * @param $arrParams
     * @param \Riki\Subscription\Model\Profile\Profile $profileSession
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
                    $addressType = $this->_helperProfile->getCustomerAddressType($addressId);
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
     * @throws LocalizedException
     */
    public function checkCustomerAmbassador($customerId){
        $customerModel = $this->customer->load($customerId);
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
     * @param $arrProductCartSession
     * @return bool
     */
    public function checkDmOnly($arrProductCartSession){
        $onlyDm = $this->calculateDeliveryDate->checkProductDm($arrProductCartSession);
        return $onlyDm;
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
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::profile_edit');
    }
}
