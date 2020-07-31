<?php

namespace Riki\Subscription\Block\Frontend\Profile;

use \Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;
use Magento\Framework\DataObject;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Store\Model\ScopeInterface;

class HanpukaiPlan extends \Magento\Framework\View\Element\Template
{
    const ARR_INFO_KEY_ORDER_TIMES = 'order_times';
    const ARR_INFO_KEY_PRODUCT_COLLECTION = 'product_collection';
    const ARR_INFO_KEY_DELIVERY_DATE = 'delivery_date';
    const ARR_INFO_KEY_QTY = 'qty';
    const ARR_INFO_KEY_PRICE = 'price';
    const ARR_INFO_ORDER_OBJECT = 'order_object';
    const ARR_INFO_KEY_TYPE = 'type';
    const MAXIMUM_SHOW_DELIVERY = 10;

    /* @var \Magento\Framework\Registry */
    protected $_registry;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_profileData;

    /* @var \Riki\Subscription\Helper\Hanpukai\Data */
    protected $hanpukaiHelper;

    /* @var \Riki\Subscription\Model\ProductCart\ProductCart */
    protected $_productCart;

    /* @var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $_productRepository;

    /* @var \Riki\Subscription\Model\Profile\ProfileFactory */
    protected $_profileFactory;

    /* @var \Riki\Subscription\Helper\Order\Simulator */
    protected $_simulator;

    /* @var \Magento\Framework\View\Page\Config */
    protected $_pageConfig;

    /* @var \Riki\Subscription\Model\Promotion\Registry */
    protected $promotionRegistry;

    /* @var \Riki\SubscriptionCourse\Model\CourseFactory */
    protected $courseFactory;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /* @var \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile */
    protected $profileResourceModelIndexer;

    /**
     * @var \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper
     */
    protected $deliveryDateGenerateHelper;

    public function __construct(
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $profileResourceModelIndexer,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Subscription\Model\ProductCart\ProductCart $productCart,
        \Riki\Subscription\Helper\Hanpukai\Data $hanpukaiHelper,
        \Riki\Subscription\Helper\Profile\Data $profileHelperData,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\Subscription\Model\Promotion\Registry $promotionRegistry,
        \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper,
        array $data = []
    ){
        $this->profileResourceModelIndexer = $profileResourceModelIndexer;
        $this->courseFactory = $courseFactory;
        $this->scopeConfig = $context->getScopeConfig();
        $this->promotionRegistry = $promotionRegistry;
        $this->_pageConfig = $context->getPageConfig();
        $this->_simulator = $simulator;
        $this->_profileFactory = $profileFactory;
        $this->_productRepository = $productRepository;
        $this->_productCart = $productCart;
        $this->hanpukaiHelper = $hanpukaiHelper;
        $this->_profileData = $profileHelperData;
        $this->_registry = $registry;
        $this->deliveryDateGenerateHelper = $deliveryDateGenerateHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get list data to show in front end
     */
    public function getArrDataShow()
    {
        $arrInfo = array();
        $profileId = $this->getProfileId();
        $profileModelObj = $this->_profileData->loadProfileModel($profileId);
        $courseModel = $this->_profileData->getCourseData($profileModelObj->getData('course_id'));
        if ($courseModel->getData('hanpukai_type') == SubscriptionType::TYPE_HANPUKAI_FIXED) {
            $arrInfo = $this->getHanpukaiInfo(
                $profileId, $profileModelObj, $courseModel, SubscriptionType::TYPE_HANPUKAI_FIXED);
        }

        if ($courseModel->getData('hanpukai_type') == SubscriptionType::TYPE_HANPUKAI_SEQUENCE) {
            $arrInfo = $this->getHanpukaiInfo(
                $profileId, $profileModelObj, $courseModel, SubscriptionType::TYPE_HANPUKAI_SEQUENCE);
        }

        return $arrInfo;
    }

    /**
     * Get hanpukai fixed info
     */
    public function getHanpukaiInfo($profileId, $profileModelObj, $courseModel, $subscriptionType)
    {
        $arrInfo = array();
        $frequencyUnit = $profileModelObj->getData('frequency_unit');
        $isSkipNextDelivery = $profileModelObj->getData('skip_next_delivery');
        $frequencyInterval = $profileModelObj->getData('frequency_interval');
        $nextDeliveryDate = $profileModelObj->getData('next_delivery_date');
        $maximumLoop = self::MAXIMUM_SHOW_DELIVERY +  $profileModelObj->getData('order_times');
        for ($i=1; $i < $maximumLoop ; $i++) {
            if ($this->hanpukaiHelper->calculateIsSubStop($profileModelObj, $i) === true) {
                break;
            }
            $deliveryNumber = $profileModelObj->getData('order_times') + $i;
            if ($subscriptionType == SubscriptionType::TYPE_HANPUKAI_FIXED) {
                $arrProductCart = $this->makeProductCartForSimulate(
                    $profileId, SubscriptionType::TYPE_HANPUKAI_FIXED, $deliveryNumber);
            } else {
                $arrProductCart = $this->makeProductCartForSimulate(
                    $profileId, SubscriptionType::TYPE_HANPUKAI_SEQUENCE, $deliveryNumber);
            }

            $arrInfo[$i][self::ARR_INFO_KEY_ORDER_TIMES] = $deliveryNumber;
            $deliveryDate = $this->_profileData->calculateDate(
                $frequencyUnit, $isSkipNextDelivery, $i-1, $frequencyInterval, $nextDeliveryDate)->format('Y/m/d');

            // NED-638: Calculation of the next delivery date
            // If subscription course setup with Next Delivery Date Calculation Option = "day of the week"
            // AND interval_unit="month"
            // AND not Stock Point
            if ($this->_profileData->isDayOfWeekAndUnitMonthAndNotStockPoint($profileModelObj)) {
                if ($profileModelObj->getData('day_of_week') != null
                    && $profileModelObj->getData('nth_weekday_of_month') != null
                ) {
                    $dayOfWeek = $profileModelObj->getData('day_of_week');
                    $nthWeekdayOfMonth = $profileModelObj->getData('nth_weekday_of_month');
                } else {
                    $dayOfWeek = date('l', strtotime($nextDeliveryDate));
                    $nthWeekdayOfMonth = $this->deliveryDateGenerateHelper->calculateNthWeekdayOfMonth(
                        $nextDeliveryDate
                    );
                }

                $deliveryDate = $this->deliveryDateGenerateHelper->getDeliveryDateForSpecialCase(
                    $deliveryDate,
                    $dayOfWeek,
                    $nthWeekdayOfMonth
                );
            }
            $arrInfo[$i][self::ARR_INFO_KEY_DELIVERY_DATE] = $deliveryDate;

            $arrProductCart = $this->calculateDeliveryDateForProductInProductCart($arrProductCart,
                $frequencyUnit, $isSkipNextDelivery, $i-1, $frequencyInterval, $profileModelObj, $deliveryDate);
            $arrInfo[$i][self::ARR_INFO_KEY_PRODUCT_COLLECTION] = $arrProductCart;
            $profileModelForSimulate = $this->getProfileModel($profileId, $i);
            $profileModelForSimulate->setData('next_delivery_date', $arrInfo[$i][self::ARR_INFO_KEY_DELIVERY_DATE]);
            $profileModelForSimulate->setData('order_times', $deliveryNumber);
            $profileModelForSimulate->setData('create_order_flag', 1); // Set create order flag = 1 because now it simulate for next order
            $objForSimulate = new DataObject();
            $objForSimulate->setData($profileModelForSimulate->getData());
            $objForSimulate->setData('course_data', $courseModel);
            $objForSimulate->setData("product_cart", $arrProductCart);
            $cacheData = $this->profileResourceModelIndexer->loadSimulateDataByProfileId($profileId, $deliveryNumber);
            if (!$cacheData || !$cacheData['data_serialized']) {
                $order = $this->simulator($objForSimulate, $profileModelForSimulate);
                $this->promotionRegistry->resetHandle();
                if ($order != false) {
                    $needDataFromOrder = $this->extractDataFromOrder($order);
                    $arrInfo[$i][self::ARR_INFO_ORDER_OBJECT] = $needDataFromOrder;
                    $dataProfileCache = $this->prepareDataForSaveToSimulateCache($order, $profileId, $deliveryNumber, $needDataFromOrder);
                    $this->profileResourceModelIndexer->saveToTableWhenSimulate($dataProfileCache);
                } else {
                    $arrInfo[$i][self::ARR_INFO_ORDER_OBJECT] = [];
                }

            } else {
                $needDataFromOrder = \Zend\Serializer\Serializer::unserialize($cacheData['data_serialized']);
                $arrInfo[$i][self::ARR_INFO_ORDER_OBJECT] = $needDataFromOrder;
            }

        }
        return $arrInfo;
    }

    /**
     * Extract Data From Order To Show on Fo And Save To DB
     *
     * @param $order
     *
     * @return array
     */
    public function extractDataFromOrder($order)
    {
        /* @var \Magento\Sales\Model\Order $order */
        $arrResult['discount_amount'] = $order->getDiscountAmount();
        $arrResult['website_id'] = $order->getStore()->getWebsiteId();
        $arrResult['total_amount'] = $order->getGrandTotal();
        foreach ($order->getAllItems() as $item) {
            $product = $item->getProduct();
            $arrResult['items'][$item->getId()]['buy_request'] = $item->getBuyRequest();
            $arrResult['items'][$item->getId()]['product_id'] = $product->getId();
            $arrResult['items'][$item->getId()]['product_name'] = $product->getName();
            $arrResult['items'][$item->getId()]['unit_case'] = $item->getUnitCase();
            $arrResult['items'][$item->getId()]['unit_qty'] = $item->getUnitQty();
            $arrResult['items'][$item->getId()]['qty_ordered'] = $item->getQtyOrdered();
            $arrResult['items'][$item->getId()]['base_row_total_incl_tax'] = $item->getBaseRowTotalInclTax();
        }
        return $arrResult;
    }

    public function prepareDataForSaveToSimulateCache($order, $profileId, $deliveryNumber, $needDataFromOrder)
    {
        /* @var \Magento\Sales\Model\Order $order */
        $data['profile_id'] = $profileId;
        $data['customer_id'] = $order->getData('customer_id');
        $data['delivery_number'] = $deliveryNumber;
        $data['data_serialized'] = \Zend\Serializer\Serializer::serialize($needDataFromOrder);
        return $data;
    }
    /**
     * Simulator with object data
     *
     * @param $objectData
     * @return array|bool
     */
    public function simulator($objectData, $profileModel)
    {
        $isList = true;
        // fix bug catalog price rule not apply when simulate data.
        if(!is_null($this->_registry->registry('subscription_profile_obj'))){
            $this->_registry->unregister('subscription_profile_obj');
        }
        $courseFactory = $this->courseFactory->create();
        $frequencyId = $courseFactory->checkFrequencyEntitiesExitOnDb(
            $objectData->getData('frequency_unit'), $objectData->getData('frequency_interval'));
        if (!is_null($this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID))) {
            $this->_registry->unregister(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID);
        }
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, (int)$frequencyId);
        $this->_registry->register('subscription_profile_obj',$profileModel);
        return $this->_simulator->createSimulatorOrderHasData($objectData, $isList);
    }

    /**
     * @param $profileId
     * @param $deliveryNumber
     *
     * @return object
     */
    public function getProfileModel($profileId, $deliveryNumber)
    {
        if($this->_profileData->getTmpProfile($profileId) === false
            && $this->_profileData->checkProfileHaveVersion($profileId) !== false) {
            $profileIdOrigin = $profileId;
            $profileIdVersion = $this->_profileData->checkProfileHaveVersion($profileId);
            if ($deliveryNumber > 1) {
                return $this->_profileFactory->create()->load($profileIdOrigin, null, true);
            } else {
                return $this->_profileFactory->create()->load($profileIdVersion);
            }
        } elseif ($this->_profileData->getTmpProfile($profileId) === false
            && $this->_profileData->checkProfileHaveVersion($profileId) === false) {
            return $this->_profileFactory->create()->load($profileId, null, true);
        } elseif ($this->_profileData->getTmpProfile($profileId) !== false
            && $this->_profileData->checkProfileHaveVersion($profileId) === false) {
            $subProfileLinkObj = $this->_profileData->getTmpProfile($profileId);
            $tmpProfileId = $subProfileLinkObj->getData('linked_profile_id');
            $mainProfileId = $subProfileLinkObj->getData('profile_id');
            if ($deliveryNumber > 1) {
                return $this->_profileFactory->create()->load($mainProfileId, null, true);
            } else {
                return $this->_profileFactory->create()->load($tmpProfileId, null, true);
            }
        } else{
            $subProfileLinkObj = $this->_profileData->getTmpProfile($profileId);
            $mainProfileId = $subProfileLinkObj->getData('profile_id');
            $tmpProfileId = $subProfileLinkObj->getData('linked_profile_id');
            $profileIdVersion = $this->_profileData->checkProfileHaveVersion($profileId);
            if ($deliveryNumber == 1) {
                return $this->_profileFactory->create()->load($profileIdVersion, null, true);
            } elseif ($deliveryNumber == 2) {
                return $this->_profileFactory->create()->load($tmpProfileId, null, true);
            } else {
                if ($subProfileLinkObj->getData('change_type') == 1) {
                    return $this->_profileFactory->create()->load($mainProfileId, null, true);
                } else {
                    return $this->_profileFactory->create()->load($tmpProfileId, null, true);
                }
            }
        }
    }

    /**
     * Calculate delivery date for product in product cart
     *
     * @param $arrProductCart
     * @param $frequencyUnit
     * @param $isSkipNextDelivery
     * @param $frequencyInterval
     * @param $profileModel
     * @param $nextDeliveryDate
     *
     * @return array
     */
    public function calculateDeliveryDateForProductInProductCart(
        $arrProductCart,
        $frequencyUnit,
        $isSkipNextDelivery,
        $i,
        $frequencyInterval,
        $profileModel,
        $nextDeliveryDate
    ) {
        foreach ($arrProductCart as $key => $obj) {
            $deliveryDate = $this->_profileData->calculateDate($frequencyUnit, $isSkipNextDelivery,
                $i, $frequencyInterval, $obj->getData('delivery_date'))->format('Y/m/d');

            if ($this->_profileData->isDayOfWeekAndUnitMonthAndNotStockPoint($profileModel)) {
                $deliveryDate = $nextDeliveryDate;
            }

            $obj->setData('delivery_date', $deliveryDate);
            $arrProductCart[$key] = $obj;
        }
        return $arrProductCart;
    }

    /**
     * Make product cart for simulate hanpukai
     *
     * @param $profileModel
     *
     * @return array
     */
    public function makeProductCartForSimulate($profileId, $hanpukaiType, $deliveryNumber)
    {
        $data = [];
        if ($this->_profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->_profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        $productCartCollection = $this->_productCart->getCollection();
        $productCartCollection->addFieldToFilter('profile_id', $profileId);
        if ($hanpukaiType == SubscriptionType::TYPE_HANPUKAI_FIXED) {
            foreach ($productCartCollection->getItems() as $item) {
                try {
                    $productModel = $this->_productRepository->getById($item->getData('product_id'));
                    if ($productModel && $productModel->getStatus() == 1) {
                        $obj = new DataObject();
                        $obj->setData($item->getData());
                        $data[$obj->getData("cart_id")] = $obj;
                    }
                } catch (\Exception $e) {
                    $this->_logger->error('Product ID #' . $item->getData('product_id') . ' was delete');
                }
            }
        } else {
            $profileModel = $this->_profileData->loadProfileModel($profileId);
            $firstItem = $productCartCollection->getFirstItem();
            $productInfo['profile_id'] = $firstItem->getData('profile_id');
            $productInfo['shipping_address_id'] = $firstItem->getData('shipping_address_id');
            $productInfo['billing_address_id'] = $firstItem->getData('billing_address_id');
            $productInfo['delivery_date'] = $firstItem->getData('delivery_date');
            $productDataHanpukaiSequence = $this->hanpukaiHelper->replaceHanpukaiSequenceProduct(
                $profileModel->getData('course_id'), $deliveryNumber, $productInfo, $profileModel->getData('hanpukai_qty'));
            foreach ($productDataHanpukaiSequence as $productData) {
                try {
                    $productData['cart_id'] = $deliveryNumber.'_'.$productData['product_id'];
                    $productModel = $this->_productRepository->getById($productData['product_id']);
                    if ($productModel && $productModel->getStatus() == 1) {
                        $obj = new DataObject();
                        $obj->setData($productData);
                        $data[$obj->getData("cart_id")] = $obj;
                    }
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }
        }
        return $data;
    }


    /**
     * Get subscription profile id
     *
     * @return int
     */
    public function getProfileId()
    {
        return $this->_registry->registry('subscription-profile-id');
    }

    /**
     * Check subscription is stop
     */
    public function subscriptionIsStop($profileModel, $courseData, $deliveryNumber)
    {
        $isStop = false;
        return $isStop;
    }

    /**
     * Get product cart product from product id
     *
     * @param $arrProductConfig
     * @param $productId
     *
     * @return object
     */
    public function getProfileProductInfo($arrProductConfig, $productId)
    {
        foreach ($arrProductConfig as $productCartId => $data) {
            if ($productId == $data->getData('product_id')) {
                return $data;
            }
        }
        return null;
    }

    /**
     * Format Currency
     *
     * @param $price
     * @param null $websiteId
     * @return mixed
     */
    public function formatCurrency($price, $websiteId = null)
    {
        return $this->_storeManager->getWebsite($websiteId)->getBaseCurrency()->format($price);
    }

    /**
     * Prepare global layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->_pageConfig->getTitle()->set(__('Hanpukai Plan'));

        return parent::_prepareLayout();
    }

    /**
     * @return mixed
     */
    public function getMessageFreeGift()
    {
        return $this->scopeConfig->getValue(
            'ampromo/messages/cart_message',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return \Riki\Subscription\Helper\Hanpukai\Data
     */
    public function getHanpukaiHelper()
    {
        return $this->hanpukaiHelper;
    }

    /**
     * @return \Riki\Subscription\Helper\Profile\Data
     */
    public function getProfileHelperData()
    {
        return $this->_profileData;
    }

    /**
     * @return \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile
     */
    public function getProfileResourceModelIndexer()
    {
        return $this->profileResourceModelIndexer;
    }

    /**
     * @return \Riki\Subscription\Model\Promotion\Registry
     */
    public function getPromotionRegistry()
    {
        return $this->promotionRegistry;
    }

}