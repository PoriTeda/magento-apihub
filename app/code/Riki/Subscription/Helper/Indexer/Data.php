<?php
namespace Riki\Subscription\Helper\Indexer;

use \Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;
use Riki\Subscription\Block\Frontend\Profile\HanpukaiPlan as HanpukaiPlan;
use Magento\Framework\DataObject;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_profileData;

    /* @var \Riki\Subscription\Helper\Hanpukai\Data */
    protected $hanpukaiHelper;

    /* @var HanpukaiPlan */
    protected $hanpukaiPlan;

    /* @var \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile */
    protected $profileResourceModelIndexer;

    /* @var \Riki\Subscription\Model\Promotion\Registry */
    protected $promotionRegistry;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connectionSales;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper
     */
    protected $deliveryDateGenerateHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        HanpukaiPlan $hanpukaiPlan,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\Helper\Context $context,
        \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper
    ) {
        $this->hanpukaiPlan = $hanpukaiPlan;
        $this->hanpukaiHelper = $this->hanpukaiPlan->getHanpukaiHelper();
        $this->_profileData = $this->hanpukaiPlan->getProfileHelperData();
        $this->profileResourceModelIndexer = $this->hanpukaiPlan->getProfileResourceModelIndexer();
        $this->promotionRegistry = $this->hanpukaiPlan->getPromotionRegistry();
        $this->profileFactory = $profileFactory;
        $this->logger = $context->getLogger();
        $this->resourceConnection = $resourceConnection;
        $this->connectionSales = $this->resourceConnection->getConnection('sales');
        $this->deliveryDateGenerateHelper = $deliveryDateGenerateHelper;
        parent::__construct($context);
    }

    /**
     * Make cache data for hanpukai
     *
     * @param $profileId
     */
    public function makeCacheDataForHanpukai($profileId)
    {
        $profileModelObj = $this->_profileData->loadProfileModel($profileId);
        $courseModel = $this->_profileData->getCourseData($profileModelObj->getData('course_id'));
        if ($courseModel->getData('hanpukai_type') == SubscriptionType::TYPE_HANPUKAI_FIXED) {
            $this->createHanpukaiDataCache(
                $profileId, $profileModelObj, $courseModel, SubscriptionType::TYPE_HANPUKAI_FIXED);
        }

        if ($courseModel->getData('hanpukai_type') == SubscriptionType::TYPE_HANPUKAI_SEQUENCE) {
            $this->createHanpukaiDataCache(
                $profileId, $profileModelObj, $courseModel, SubscriptionType::TYPE_HANPUKAI_SEQUENCE);
        }
    }

    public function createHanpukaiDataCache($profileId, $profileModelObj, $courseModel, $subscriptionType)
    {
        $arrInfo = array();
        $frequencyUnit = $profileModelObj->getData('frequency_unit');
        $isSkipNextDelivery = $profileModelObj->getData('skip_next_delivery');
        $frequencyInterval = $profileModelObj->getData('frequency_interval');
        $nextDeliveryDate = $profileModelObj->getData('next_delivery_date');
        $maximumLoop = HanpukaiPlan::MAXIMUM_SHOW_DELIVERY +  $profileModelObj->getData('order_times');
        for ($i=1; $i < $maximumLoop; $i++) {
            if ($this->hanpukaiHelper->calculateIsSubStop($profileModelObj, $i) === true) {
                break;
            }
            $deliveryNumber = $profileModelObj->getData('order_times') + $i;
            if ($subscriptionType == SubscriptionType::TYPE_HANPUKAI_FIXED) {
                $arrProductCart = $this->hanpukaiPlan->makeProductCartForSimulate(
                    $profileId, SubscriptionType::TYPE_HANPUKAI_FIXED, $deliveryNumber);
            } else {
                $arrProductCart = $this->hanpukaiPlan->makeProductCartForSimulate(
                    $profileId, SubscriptionType::TYPE_HANPUKAI_SEQUENCE, $deliveryNumber);
            }

            $arrInfo[$i][HanpukaiPlan::ARR_INFO_KEY_ORDER_TIMES] = $deliveryNumber;
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
            $arrInfo[$i][HanpukaiPlan::ARR_INFO_KEY_DELIVERY_DATE] = $deliveryDate;

            $arrProductCart = $this->hanpukaiPlan->calculateDeliveryDateForProductInProductCart($arrProductCart,
                $frequencyUnit, $isSkipNextDelivery, $i-1, $frequencyInterval, $profileModelObj, $deliveryDate);
            $arrInfo[$i][HanpukaiPlan::ARR_INFO_KEY_PRODUCT_COLLECTION] = $arrProductCart;
            $profileModelForSimulate = $this->hanpukaiPlan->getProfileModel($profileId, $i);
            $profileModelForSimulate->setData('next_delivery_date', $arrInfo[$i][HanpukaiPlan::ARR_INFO_KEY_DELIVERY_DATE]);
            $profileModelForSimulate->setData('order_times', $deliveryNumber);
            $profileModelForSimulate->setData('create_order_flag', 1); // Set create order flag = 1 because now it simulate for next order
            $objForSimulate = new DataObject();
            $objForSimulate->setData($profileModelForSimulate->getData());
            $objForSimulate->setData('course_data', $courseModel);
            $objForSimulate->setData("product_cart", $arrProductCart);
            $order = $this->hanpukaiPlan->simulator($objForSimulate, $profileModelForSimulate);
            $this->promotionRegistry->resetHandle();
            if ($order != false) {
                $needDataFromOrder = $this->hanpukaiPlan->extractDataFromOrder($order);
                $arrInfo[$i][HanpukaiPlan::ARR_INFO_ORDER_OBJECT] = $needDataFromOrder;
                $dataProfileCache = $this->hanpukaiPlan->prepareDataForSaveToSimulateCache(
                    $order, $profileId, $deliveryNumber, $needDataFromOrder);
                $this->profileResourceModelIndexer->saveToTableWhenSimulate($dataProfileCache);
            }
        }
    }
    /**
     * @param $simulatorOrder
     * @return array|bool
     */
    public function prepareData($simulatorOrder)
    {
        if ($simulatorOrder) {
            $data = [
                'discount' => $simulatorOrder->getDiscountAmount(),
                'shipping_fee' => $simulatorOrder->getShippingInclTax(),
                'payment_method_fee' => $simulatorOrder->getFee(),
                'wrapping_fee' => $simulatorOrder->getData('gw_items_base_price_incl_tax'),
                'total_amount' => $simulatorOrder->getGrandTotal()
            ];
            return $data;
        }
        return false;
    }
    /**
     * @param $data
     * @return $this
     * @throws \Exception
     */
    public function saveToTable($data)
    {
        $this->connectionSales->beginTransaction();

        try {
            $this->connectionSales->insertMultiple(
                $this->connectionSales->getTableName('subscription_profile_simulate_cache'),
                $data
            );
            $this->connectionSales->commit();
        } catch (\Exception $e) {
            $this->connectionSales->rollback();
            throw $e;
        }

        return $this;
    }

    /**
     * @param $profileId
     * @param $simulatorOrder
     */
    public function updateDataProfileCache($profileId, $simulatorOrder) {
        $dataSimulate = $this->prepareData($simulatorOrder);
        if ($dataSimulate) {
            $serializedData = \Zend\Serializer\Serializer::serialize($dataSimulate);
            $this->connectionSales->beginTransaction();

            try {
                $bind = ['data_serialized' => $serializedData];
                $this->connectionSales->update(
                    $this->connectionSales->getTableName('subscription_profile_simulate_cache'),
                    $bind,
                    ['profile_id = ?' => $profileId]
                );
                $this->connectionSales->commit();
            } catch (\Exception $e) {
                $this->connectionSales->rollback();
            }
        }
    }
    /**
     * @param $profileId
     */
    public function updateProfile($profileId) {

        $profileModel = $this->profileFactory->create()->load($profileId);

        if($profileModel->getId()) {
            $profileModel->setData('reindex_flag',0);
            try {
                $profileModel->save();
            }catch (\Exception $e){
                $this->logger->critical($e);
            }
        }
    }
}