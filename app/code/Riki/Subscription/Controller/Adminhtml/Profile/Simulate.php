<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile;

use Magento\Framework\DataObject;
use Riki\Subscription\Helper\Order\Data as SubscriptionOrerHelper;

class Simulate extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\Subscription\Model\Profile\Profile $profileModel
     */
    protected $profileModel;
    /**
     * @var \Magento\Customer\Model\Session $customerSession
     */
    protected $customerSession;
    /**
     * @var \Magento\Framework\Message\ManagerInterface $messageManager
     */
    protected $messageManager;
    /**
     * @var \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    protected $resultPageFactory;
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry $coreRegistry
     */
    protected $coreRegistry = null;
    /**
     * @var \Riki\Subscription\Helper\Order\Simulator $profileEmulator
     */
    protected $profileEmulatorHelper = null;
    /**
     * @var \Riki\Subscription\Model\Frequency\FrequencyFactory
     */
    protected $frequencyFactory;
    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $deliveryDate;
    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $customerAddress;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJson;
    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    private $_extensibleDataObjectConverter;
    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $_helperWrapping;
    /**
     * @var \Magento\Tax\Model\TaxCalculation
     */
    protected $_taxCalculation;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory
     */
    protected $wrappingCollectionFactory;

    protected $profileRepository;

    protected $groupDeliveryType = [];

    private $profileCache;

    protected $_controllerHelper;

    protected $cartData;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subOrderHelper;

    protected $subscriptionValidator;

    /**
     * Simulate constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Riki\Subscription\Model\Profile\Profile $profileModel
     * @param \Riki\Subscription\Helper\Order\Simulator $profileEmulatorHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Subscription\Model\Frequency\FrequencyFactory $frequencyFactory
     * @param \Riki\DeliveryType\Model\DeliveryDate $deliveryDate
     * @param \Magento\Customer\Model\AddressFactory $customerAddress
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param \Magento\GiftWrapping\Helper\Data $gwHelperData
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $gwCollection
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Riki\Subscription\Helper\Order $subOrderHelper
     * @param \Riki\Subscription\Helper\Profile\Controller\Save $controllerHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Riki\Subscription\Model\Profile\Profile $profileModel,
        \Riki\Subscription\Helper\Order\Simulator $profileEmulatorHelper,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Model\Frequency\FrequencyFactory $frequencyFactory,
        \Riki\DeliveryType\Model\DeliveryDate $deliveryDate,
        \Magento\Customer\Model\AddressFactory $customerAddress,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Magento\GiftWrapping\Helper\Data $gwHelperData,
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $gwCollection,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\Subscription\Helper\Order $subOrderHelper,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCache,
        \Riki\Subscription\Helper\Profile\Controller\Save $controllerHelper
    ) {
        $this->profileModel = $profileModel;
        $this->customerSession = $customerSession;
        $this->messageManager = $context->getMessageManager();
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->profileEmulatorHelper = $profileEmulatorHelper;
        $this->logger = $logger;
        $this->frequencyFactory = $frequencyFactory;
        $this->deliveryDate = $deliveryDate;
        $this->customerAddress = $customerAddress;
        $this->_resultJson = $jsonFactory;
        $this->_extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->_helperWrapping = $gwHelperData;
        $this->_taxCalculation  = $taxCalculation;
        $this->_storeManager = $storeManager;
        $this->wrappingCollectionFactory = $gwCollection;
        $this->profileRepository = $profileRepository;
        $this->profileCache = $profileCache;
        $this->subOrderHelper = $subOrderHelper;
        $this->subscriptionValidator = $subscriptionValidator;
        $this->_controllerHelper = $controllerHelper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function execute()
    {
        $arrPost = $this->getRequest()->getParams();
        /** @var \Riki\Subscription\Model\Profile\Profile $objProfileCache */
        $objProfileCache = $this->getProfileCache();
        $profileData = new DataObject($arrPost);
        $profileData->setData('is_delete_stock_point', $objProfileCache->getData('is_delete_stock_point'));
        /*Validate $data*/
        /*profile id */
        $profileId = $this->getRequest()->getParam('profile_id', false);
        /* if profile id is missing we put an error and redirect them to previous page */
        if (!$profileId) {
            $this->messageManager->addError(__("Profile with is not exists"));
            return $this->_resultJson->create()->setData(['status'=>false]);
        }

        $profileModel = $this->profileModel->load($profileId);
        if ($this->getRequest()->isXmlHttpRequest()) {
            /** @var \Riki\Subscription\Model\Data\ApiProfile $profileDataModel */
            $profileDataModel = $this->profileRepository->get($profileId);
            $this->coreRegistry->register('subscription_profile_obj', $profileModel);
            $this->coreRegistry->register('subscription_profile_data', $profileDataModel);
        }
        $profileData->setData('course_id', $profileModel->getData('course_id'));
        /** if has stock point then frequencty_id is of stockpoint */
        $stockPointData = $this->issetStockPoint($arrPost);
        if ($stockPointData) {
            if (isset($stockPointData['frequency_unit']) && isset($stockPointData['frequency_interval'])) {
                $profileData->setData('frequency_unit', $stockPointData['frequency_unit']);
                $profileData->setData('frequency_interval', $stockPointData['frequency_interval']);
            } else {
                return $this->_resultJson->create()->setData(
                    ['status'=>false, 'messages' => __('Frequency Unit Or Frequency Interval not found.')]
                );
            }
        } else {
            /*Frequency*/
            $frequencyId = $arrPost['frequency_id'];
            $frequencyModel = $this->frequencyFactory->create()->load($frequencyId);
            if ($frequencyModel->getId()) {
                /*Frequency Unit*/
                if ($frequencyModel->getData('frequency_unit')) {
                    $profileData->setData('frequency_unit', $frequencyModel->getData('frequency_unit'));
                } else {
                    return $this->_resultJson->create()->setData(['status'=>false]);
                }
                /*Course ID*/
                if ($frequencyModel->getData('frequency_interval')) {
                    $profileData->setData('frequency_interval', $frequencyModel->getData('frequency_interval'));
                } else {
                    return $this->_resultJson->create()->setData(['status'=>false]);
                }
            }

            $profileData->setData(
                SubscriptionOrerHelper::PROFILE_STOCK_POINT_BUCKET_ID,
                $profileModel->getstockPointProfileBucketId()
            );
        }

        /*Customer Id*/
        if ($profileModel->getCustomerId()) {
            $profileData->setData('customer_id', $profileModel->getCustomerId());
        } else {
            return $this->_resultJson->create()->setData(['status'=>false]);
        }
        /*Store ID*/
        if ($profileModel->getData('store_id')) {
            $profileData->setData('store_id', $profileModel->getData('store_id'));
        } else {
            return $this->_resultJson->create()->setData(['status'=>false]);
        }
        /*order times*/
        $profileData['order_times'] = $profileModel->getData('order_times');

        /*Shipping method*/
        $profileData['shipping_condition'] = $profileModel->getData('shipping_condition');

        $profileData['order_channel'] = $profileModel->getData('order_channel');

        $arrProductCartSession = $objProfileCache->getProductCart();
        $arrProductCart = $this->profileModel->getProductCartData();
        $arrProductCart = $this->_controllerHelper->removeDuplicateAndMerge($arrProductCart);
        $arrProductCartCacheNew = [];
        /*Order items*/
        foreach ($arrPost['productcat_id'] as $addressId => $arrPCartByDL) {
            foreach ($arrPCartByDL as $deliveryType => $arrPCartId) {
                foreach ($arrPCartId as $i => $pcartId) {
                    if (!$pcartId) continue;
                    if (isset($arrProductCartSession[$pcartId])) {
                        $objProductCat = $arrProductCartSession[$pcartId];
                    } elseif (isset($arrProductCart[$pcartId])) {
                        $objProductCat = $arrProductCart[$pcartId];
                    } else {
                        $objProductCat = null;
                    }

                    if(empty($objProductCat) || empty($objProductCat->getData("cart_id"))) {
                        return $this->_resultJson->create()->setData(['status'=>false]);
                    }

                    /** Collect information */
                    $productCatQty = $objProductCat->getData("qty");
                    if (isset($arrPost["product_qty"]) && isset($arrPost["product_qty"][$pcartId])) {
                        $productCatQty = isset($arrPost["product_qty"]) ? $arrPost["product_qty"][$pcartId] : $objProductCat->getData("qty");
                    }

                    if (isset($arrPost["product_qty_case"]) && isset($arrPost["product_qty_case"][$pcartId])) {
                        $productCatQty = $arrPost["product_qty_case"][$pcartId];
                        $productCatUnitQty = $arrPost["product_unit_qty"][$pcartId];
                        $productCatQty = $productCatQty * $productCatUnitQty;
                    }

                    $deliveryDate = isset($arrPost['next_delivery']) ?   $arrPost['next_delivery'][$addressId][$deliveryType] : $objProductCat->getData("delivery_date");
                    $deliveryTimeSlot = isset($arrPost['time_slot'])
                    && isset($arrPost['time_slot'][$addressId])
                    && isset($arrPost['time_slot'][$addressId][$deliveryType]) ? $arrPost['time_slot'][$addressId][$deliveryType] : $objProductCat->getData("delivery_time_slot");

                    $productGift = (isset($arrPost["gift"]) && isset($arrPost["gift"][$addressId][$pcartId])) ? $arrPost["gift"][$addressId][$pcartId] : $objProductCat->getData("gw_id");

                    /*skip seasonal product*/

                    $isSkip = (isset($arrPost["is_skip_productcat"]) and isset($arrPost["is_skip_productcat"][$pcartId]) )? $arrPost["is_skip_productcat"][$pcartId] : null;
                    if ($isSkip) {
                        $skipFrom = (isset($arrPost["skip_from_productcat"]) and isset($arrPost["skip_from_productcat"][$pcartId])) ? $arrPost["skip_from_productcat"][$pcartId] : $objProductCat->getData("skip_from");
                        $skipTo = (isset($arrPost["skip_to_productcat"]) and isset($arrPost["skip_to_productcat"][$pcartId])) ? $arrPost["skip_to_productcat"][$pcartId] : $objProductCat->getData("skip_to");
                    }
                    $isAddition = (isset($arrPost['is_addition']) and isset($arrPost["is_addition"][$pcartId]))?$arrPost["is_addition"][$pcartId]:$objProductCat->getData("is_addition");
                    $oldShippingAddressId = $objProductCat->getData("shipping_address_id");
                    $shippingAddressId = $oldShippingAddressId;

                    if (isset($arrPost['address'][$addressId][$deliveryType])) {
                        $shippingAddressId = $arrPost['address'][$addressId][$deliveryType];
                    }

                    $objProductCat->setData('delivery_date', $deliveryDate);
                    $objProductCat->setData('delivery_time_slot', $deliveryTimeSlot);
                    $objProductCat->setData('shipping_address_id', $shippingAddressId);
                    if (!(isset($arrPost['is_added']) and $arrPost['is_added'] == $objProductCat->getData('product_id'))) {
                        $objProductCat->setData('qty', $productCatQty);
                    }
                    $objProductCat->setData('gw_id', $productGift);
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
                    $arrProductCartCacheNew[$pcartId] = $objProductCat;

                    $productobj = new DataObject();
                    $this->cartData[] = ['product' => $productobj->setData('id', $objProductCat->getData('product_id')),
                        'qty' => $productCatQty ];
                }

                $this->groupDeliveryType = $deliveryType;
            }
        }
        /** Validate maximum for every product*/
        $validateMaximum = $this->subscriptionValidator->setProfileId($profileId)
            ->setProductCarts($arrProductCartCacheNew)
            ->validateMaximumQtyRestriction();
        if ($validateMaximum['error'] && !empty($validateMaximum['product_errors'])) {
            $message = $this->subscriptionValidator->getMessageMaximumError(
                $validateMaximum['product_errors'],
                $validateMaximum['maxQty']
                );
                $returnData = [
                    'status' => false,
                    'message' => $message
                ];
            return $this->_resultJson->create()->setData($returnData);
        }

        $objProfileCache->setData("product_cart", $arrProductCartCacheNew);
        /** Save to cache */
        $this->profileCache->save($objProfileCache);
        try {
            $emulateOrder =  $this->profileEmulatorHelper->createSimulatorOrderHasData($profileData, null, true);
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
            return $this->_resultJson->create()->setData(['status'=>false]);
        }

        if (!\Zend_Validate::is($emulateOrder, 'NotEmpty')) {
            return $this->_resultJson->create()->setData(['status'=>false]);
        }
        if ($emulateOrder instanceof \Riki\Subscription\Model\Emulator\Order) {
            $simulateOrderFlatData = $this->_extensibleDataObjectConverter->toNestedArray(
                $emulateOrder,
                [],
                '\Magento\Sales\Api\Data\OrderInterface'
            );
            $simulateOrderFlatData["gw_amount"] = $this->getGiftWrappingFee($profileData);
            $simulateOrderFlatData["fee"] = $emulateOrder->getFee();
            $simulateOrderFlatData["base_fee"] = $emulateOrder->getBaseFee();
            $simulateOrderFlatData["used_point_amount"] = $emulateOrder->getData('used_point_amount');
            $simulateOrderFlatData["bonus_point_amount"] = $emulateOrder->getBonusPointAmount();
            $returnData = ['status' => true, 'message' => $simulateOrderFlatData];
            $subscriptionCourse = $this->getSubscriptionCourse($profileData->getData('course_id'));
            $validateResults = $this->subOrderHelper->validateAmountRestriction(
                $emulateOrder,
                $subscriptionCourse,
                $profileModel
            );
            if (!$validateResults['status']) {
                $returnData = [
                    'status' => false,
                    'message' => $validateResults['message']
                ];
            }
            return $this->_resultJson->create()->setData($returnData);
        }
    }

    /**
     * Get subscription course
     * @param $subCourseId
     * @return $this
     */
    protected function getSubscriptionCourse($subCourseId)
    {
        return $this->subOrderHelper->loadCourse($subCourseId);
    }

    /**
     * @return \Riki\Subscription\Model\Profile\Profile|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getProfileCache()
    {
        $profileId = $this->_request->getParam('profile_id');
        $cacheData = $this->profileCache->initProfile($profileId);
        if (is_object($cacheData) && $sessionWrapper = $cacheData->getProfileData()) {
            if (isset($sessionWrapper[$profileId])) {
                return $sessionWrapper[$profileId];
            }
        }
        return false;
    }

    public function issetStockPoint($data)
    {
        if (isset($data["stock_point_data_post"]) && !empty($data["stock_point_data_post"])) {
            return \Zend_Json::decode($data["stock_point_data_post"]);
        }
        return false;
    }
    /**
     * @param $profileData
     * @return int
     */
    public function getGiftWrappingFee($profileData){
        $store = $this->_storeManager->getStore($profileData->getData('store_id'));
        $wrappingTax = $this->_helperWrapping->getWrappingTaxClass($store);
        $wrappingRate = $this->_taxCalculation->getCalculatedRate($wrappingTax);

        $arrProduct = $profileData->getData("product_cart");
        $wrappingFee = 0;
        $gwData = isset($profileData['gift'])?$profileData['gift']:[];
        foreach ($gwData as $addressId => $_arrData) {
            foreach ($_arrData as $cartId => $gwId) {
                $finalPrice = 0;
                if ($gwId > 0 && $gwId != null) {
                    $productCartData = isset($arrProduct[$cartId])?$arrProduct[$cartId]:null;
                    $giftpriceCollection = $this->wrappingCollectionFactory->create()
                        ->addFieldToFilter('wrapping_id', $gwId)
                        ->setPageSize(1)
                        ->addWebsitesToResult()->load();
                    if ($giftpriceCollection) {
                        $basePrice = $giftpriceCollection->getFirstItem()->getData('base_price');

                        if ($basePrice > 0) {
                            $taxRate = $wrappingRate / 100;
                            $finalPrice = $basePrice + ($taxRate*$basePrice);
                        }
                        if (isset($productCartData['unit_case'])) {
                            if (\Riki\CreateProductAttributes\Model\Product\UnitEc::UNIT_CASE == $productCartData['unit_case']) {
                                $unitQty = (null != $productCartData['unit_qty']) ? $productCartData['unit_qty'] : 1;
                                $wrappingFee += ((int)$finalPrice * ($profileData['product_qty'][$cartId] / $unitQty));
                            } else {
                                $wrappingFee += ((int)$finalPrice * $profileData['product_qty'][$cartId] );
                            }
                        }
                    }
                }
            }
        }
        return $wrappingFee;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::profile_edit');
    }
}
