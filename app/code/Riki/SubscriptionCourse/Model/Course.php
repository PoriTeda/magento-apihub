<?php

namespace Riki\SubscriptionCourse\Model;

use Riki\SubscriptionCourse\Api\Data\SubscriptionCourseInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;

/**
 * Subscription Course data model
 *
 * @method \Riki\SubscriptionCourse\Model\ResourceModel\Course _getResource()
 * @method \Riki\SubscriptionCourse\Model\ResourceModel\Course getResource()
 */
class Course extends \Magento\Framework\Model\AbstractModel implements SubscriptionCourseInterface, IdentityInterface
{
    const TABLE = 'subscription_course';

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    const DELAYED_PAYMENT_VALIDATION_ERROR_NOT_ALLOWED_PAYMENT = 1;

    const UNIT_WEEK = 'week';
    const UNIT_MONTH = 'month';

    const EXCLUDE_BUFFER_DAYS_ENABLED = 1;
    const EXCLUDE_BUFFER_DAYS_DISABLED = 0;

    const VISIBILITY_NONE = 0;
    const VISIBILITY_FRONTEND = 1;
    const VISIBILITY_BACKEND = 2;
    const VISIBILITY_ALL = 3;

    const DESIGN_NORMAL = 'normal';
    const DESIGN_BLACK = 'black';

    const CAPTURE_AMOUNT_PER_SKU = 0;
    const CAPTURE_AMOUNT_PER_ORDER_AMOUNT = 1;

    const CACHE_TAG = 'subscription_course';

    const IS_DELAY_PAYMENT = 'is_delay_payment';

    const IS_SHOPPING_POINT_DEDUCTION = 'is_shopping_point_deduction';
    const CAPTURED_AMOUNT_CALCULATION_OPTION = 'captured_amount_calculation_option';

    const SUBSCRIPTION_PAYMENT_CREDIT_CARD = 1;
    const SUBSCRIPTION_PAYMENT_COD = 2;
    const SUBSCRIPTION_PAYMENT_CSV = 3;
    const SUBSCRIPTION_PAYMENT_INVOICE_PAYMENT = 4;
    const SUBSCRIPTION_PAYMENT_NP_ATOBARAI = 5;

    const NEXT_DELIVERY_DATE_CALCULATION_OPTION_DAY_OF_WEEK = 'day_of_week';

    const NEXT_DELIVERY_DATE_CALCULATION_OPTION_DAY_OF_MONTH = 'day_of_month';

    protected $eventPrefix = 'subscription_course';
    protected $eventObject = 'course';
    const TOTAL_AMOUNT_OPTION_SECOND_ORDER = 0;
    const TOTAL_AMOUNT_OPTION_ALL_ORDER = 1;

    /**
     * @var \Riki\SubscriptionFrequency\Helper\Data
     */
    protected $frequencyHelper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Riki\DeliveryType\Model\Config\DeliveryDateSelection
     */
    protected $deliveryDateSelectionConfig;

    /**
     * Course constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Riki\DeliveryType\Model\Config\DeliveryDateSelection $deliveryDateSelectionConfig
     * @param ResourceModel\Course|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\DeliveryType\Model\Config\DeliveryDateSelection $deliveryDateSelectionConfig,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->resourceConnection = $resourceConnection;
        $this->frequencyHelper = $frequencyHelper;
        $this->deliveryDateSelectionConfig = $deliveryDateSelectionConfig;
    }

    protected function _construct()
    {
        $this->_init('Riki\SubscriptionCourse\Model\ResourceModel\Course');
    }

    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }

    public function getDurationUnits()
    {
        return [self::UNIT_WEEK => __('Week'), self::UNIT_MONTH => __('Month')];
    }

    public function getExcludeBufferDaysOptions()
    {
        return [self::EXCLUDE_BUFFER_DAYS_ENABLED => __('Yes'), self::EXCLUDE_BUFFER_DAYS_DISABLED => __('No')];
    }

    public function getVisibility()
    {
        return [
            ['value' => self::VISIBILITY_NONE, 'label' => __('None')],
            ['value' => self::VISIBILITY_FRONTEND, 'label' => __('Front-end')],
            ['value' => self::VISIBILITY_BACKEND, 'label' => __('Back-end')],
            ['value' => self::VISIBILITY_ALL, 'label' => __('All')]
        ];
    }

    /**
     * GetDesign
     *
     * @return array
     */
    public function getDesign()
    {
        return [
            ['value' => self::DESIGN_NORMAL, 'label' => __('Normal')],
            ['value' => self::DESIGN_BLACK, 'label' => __('Black')]
        ];
    }

    public function getYesNo()
    {
        return [
            ['value' => 1, 'label' => __('Yes')],
            ['value' => 0, 'label' => __('No')]
        ];
    }

    /**
     * get options for dropdownlist Captured amount calculation option
     * @return array
     */
    public function getOptionCaptureAmount()
    {
        return [
            ['value' => self::CAPTURE_AMOUNT_PER_SKU, 'label' => __('Calculated per SKU')],
            ['value' => self::CAPTURE_AMOUNT_PER_ORDER_AMOUNT, 'label' => __('Calculated per Order Amount')]
        ];
    }

    public function getAvailableWebsites($id = null)
    {
        if (is_null($id) && $this->getId()) {
            $id = $this->getId();
        }
        return $this->getResource()->getWebsiteIds($id);
    }

    public function getFrequencies($id = null)
    {
        if (is_null($id) && $this->getId()) {
            $id = $this->getId();
        }
        return $this->getResource()->getFrequencyIds($id);
    }

    public function getFrequencyEntities($id = null)
    {
        if (is_null($id) && $this->getId()) {
            $id = $this->getId();
        }
        return $this->getResource()->getFrequencyEntities($id);
    }

    public function getFrequencyValuesForForm($addFirstOption = false)
    {
        $frequencies = $this->getResource()->getAllFrequencies();

        $options = [];
        if ($addFirstOption) {
            $options[] = [
                'value' => '',
                'label' => __('Please select a option')
            ];
        }

        foreach ($frequencies as $frequency) {
            $options[] = [
                'value' => $frequency['frequency_id'],
                'label' => $this->frequencyHelper->formatFrequency($frequency['frequency_interval'], $frequency['frequency_unit'])
            ];
        }

        return $options;
    }

    public function getFrequencyForHanpukai()
    {
        $frequencies = $this->getResource()->getAllFrequencies();
        $options = [];
        foreach ($frequencies as $frequency) {
            $options[$frequency['frequency_id']] = [
                'value' => $frequency['frequency_id'],
                'label' => $this->frequencyHelper->formatFrequency($frequency['frequency_interval'], $frequency['frequency_unit']),
                'frequency_interval' => $frequency['frequency_interval'],
                'frequency_unit' => $frequency['frequency_unit']
            ];
        }
        return $options;
    }

    /**
     * Use Riki\SubscriptionCourse\Model\Course\Source\Payment instead
     */
    public function getAvailablePayments()
    {
        return [
            ['value' => self::SUBSCRIPTION_PAYMENT_CREDIT_CARD, 'label' => __('Credit card')],
            ['value' => self::SUBSCRIPTION_PAYMENT_COD, 'label' => __('COD')],
            ['value' => self::SUBSCRIPTION_PAYMENT_CSV, 'label' => __('CVS')]
        ];
    }

    /**
     * Convert payment method
     * payment id => payment code
     * payment code => payment id (reverse = true)
     */
    public function mapPaymentMethod($key, $reverse = false)
    {
        $list = [
            self::SUBSCRIPTION_PAYMENT_CREDIT_CARD => 'paygent',
            self::SUBSCRIPTION_PAYMENT_COD => 'cashondelivery',
            self::SUBSCRIPTION_PAYMENT_CSV => 'cvspayment',
            self::SUBSCRIPTION_PAYMENT_INVOICE_PAYMENT => 'invoicedbasedpayment',
            self::SUBSCRIPTION_PAYMENT_NP_ATOBARAI => NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ];
        if (!$reverse) {
            if (isset($list[$key])) {
                return $list[$key];
            }
        } else {
            $listReverse = array_flip($list);
            if (isset($listReverse[$key])) {
                return $listReverse[$key];
            }
        }
        return false;
    }

    public function getId()
    {
        return $this->getData(self::COURSE_ID);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getName()
    {
        return $this->getData(self::COURSE_NAME);
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->getData(self::COURSE_CODE);
    }

    /**
     * @return string
     */
    public function getSubscriptionType()
    {
        return $this->getData(self::SUBSCRIPTION_TYPE);
    }

    /**
     * @return array
     */
    public function getCoursesForForm()
    {
        $courses = $this->getResource()->getAllCourses();
        $options = [];
        foreach ($courses as $course) {
            $options[] = [
                'value' => $course['course_id'],
                'label' => __($course['course_name'])
            ];
        }
        return $options;
    }

    /**
     * @param int $allowChangeNextDeliveryDate
     * @return Course
     */
    public function setAllowChangeNextDeliveryDate($allowChangeNextDeliveryDate)
    {
        return $this->setData(self::ALLOW_CHANGE_NEXT_DELIVERY_DATE, $allowChangeNextDeliveryDate);
    }

    /**
     * @return mixed
     */
    public function getAllowChangeNextDeliveryDate()
    {
        return $this->getData(self::ALLOW_CHANGE_NEXT_DELIVERY_DATE);
    }

    /**
     * @return mixed
     */
    public function getHanpukaiDeliveryDateAllowed()
    {
        return $this->getData(self::HANPUKAI_DELIVERY_DATE_ALLOWED);
    }

    /**
     * @param int $hanpukaiDeliveryDateAllowed
     * @return Course
     */
    public function setHanpukaiDeliveryDateAllowed($hanpukaiDeliveryDateAllowed)
    {
        return $this->setData(self::HANPUKAI_DELIVERY_DATE_ALLOWED, $hanpukaiDeliveryDateAllowed);
    }

    /**
     * validate data
     *
     * @return array
     */
    public function validate()
    {
        $errors = [];

        //check subscription type
        if ($this->getSubscriptionType() == SubscriptionType::TYPE_HANPUKAI) {
            switch ($this->getHanpukaiType()) {
                case SubscriptionType::TYPE_HANPUKAI_FIXED:
                    break;
                case SubscriptionType::TYPE_HANPUKAI_SEQUENCE:
                    $errors = array_merge($errors, $this->_validateSequenceProducts());
                    break;
                default:
                    $errors[] = __('Hanpukai type is invalid');
            }

            if ($this->hasData('hanpukai_delivery_date_allowed') && $this->getData('hanpukai_delivery_date_allowed') == 0) {
                if ($this->hasData('hanpukai_first_delivery_date')
                    && $this->getData('hanpukai_first_delivery_date') != ''
                    && $this->hasCloseDate() && $this->getCloseDate() != ""
                ) {
                    $firstDelivery = $this->getData('hanpukai_first_delivery_date');
                    $subCloseDate = $this->getData('close_date');

                    $firstDelivery = new \DateTime($firstDelivery);
                    $subCloseDate = new \DateTime($subCloseDate);

                    if ($firstDelivery <= $subCloseDate) {
                        $errors[] = __('Make sure the First Delivery Date is later than Close Date .');
                    }
                }
            }

            if ($this->hasData('hanpukai_delivery_date_allowed') && $this->getData('hanpukai_delivery_date_allowed') == 1) {
                if ($this->hasData('hanpukai_delivery_date_from') && $this->hasData('hanpukai_delivery_date_to')
                    && $this->getData('hanpukai_delivery_date_from') != '' && $this->getData('hanpukai_delivery_date_to') != ''
                ) {
                    $deliveryFrom = $this->getData('hanpukai_delivery_date_from');
                    $deliveryTo = $this->getData('hanpukai_delivery_date_to');

                    $deliveryFrom = new \DateTime($deliveryFrom);
                    $deliveryTo = new \DateTime($deliveryTo);

                    if ($deliveryFrom > $deliveryTo) {
                        $errors[] = __('Make sure Delivery Date To is later than Delivery Date From.');
                    }
                }
            }
        }

        if ($this->hasLaunchDate() && $this->hasCloseDate() && $this->getCloseDate() != "") {
            $fromDate = $this->getLaunchDate();
            $toDate = $this->getCloseDate();

            $fromDate = new \DateTime($fromDate);
            $toDate = new \DateTime($toDate);

            if ($fromDate > $toDate) {
                $errors[] = __('Make sure the Close date is later than or the same as the Launch date.');
            }
        }

        if ($this->hasCategoryIds() || $this->isHanpukai()) {
            $arrCategoryIds = $this->getData('category_ids');
            if ($this->hasAdditionalCategoryIds()) {
                $arrAdditionalCategoryIds = $this->getData('additional_category_ids');
                if (count(array_intersect($arrCategoryIds, $arrAdditionalCategoryIds)) > 0) {
                    $errors[] = __('Category of main category must difference category in additional category.');
                }
            }
            if ($this->hasProfileCategoryIds()) {
                $arrProfileCategoryIds = $this->getData('profile_category_ids');
                if (count(array_intersect($arrCategoryIds, $arrProfileCategoryIds)) > 0) {
                    $errors[] = __('Category of main category must difference category in profile edit categories.');
                }
                if ($this->hasAdditionalCategoryIds()) {
                    if (count(array_intersect($arrAdditionalCategoryIds, $arrProfileCategoryIds)) > 0) {
                        $errors[] = __('Category of additional category must difference category in profile edit categories.');
                    }
                }
            }
        } else {
            $errors[] = __('Make sure Categories are not null.');
        }
        return $errors;
    }

    /**
     * Validate conditions delay payment
     *
     * @return int
     */
    protected function validateDelayPayment()
    {
        if ($this->getIsDelayPayment()) {
            $delayPaymentCode = self::SUBSCRIPTION_PAYMENT_CREDIT_CARD;
            if (count($this->getPaymentIds()) != 1 || !in_array($delayPaymentCode, $this->getPaymentIds())) {
                return self::DELAYED_PAYMENT_VALIDATION_ERROR_NOT_ALLOWED_PAYMENT;
            }
        }
        return 0;
    }

    /**
     * convert frequency
     *
     * @param $frequencyUnit
     * @param $strFrequency
     * @param $arrData
     * @return array|null
     */
    public function checkFrequency($frequencyUnit, $strFrequency, $arrData)
    {
        $strFrequency = trim(str_replace($arrData, '', $strFrequency));
        $arrFrequencyIds = explode('/', $strFrequency);
        if (is_array($arrFrequencyIds) && count($arrFrequencyIds) > 0) {
            $tmp = [];
            foreach ($arrFrequencyIds as $frequencyId) {
                $frequency = $this->checkFrequencyEntitiesExitOnDb($frequencyUnit, $frequencyId);
                if ($frequency != null) {
                    $tmp[] = trim($frequency);
                }
            }
            if (count($tmp) > 0) {
                return $tmp;
            }
        }
        return null;
    }

    /**
     * check Frequency id
     *
     * @param $frequencyId
     * @return bool
     */
    public function checkFrequencyEntitiesExitOnDb($frequencyUnit, $frequencyId)
    {
        $connection = $this->resourceConnection->getConnection('sales');
        $select = $connection->select()
            ->from([$this->getResource()->getTable('subscription_frequency')])
            ->where("frequency_unit = '$frequencyUnit' AND frequency_interval = $frequencyId ");
        $data = $connection->fetchRow($select);
        if (isset($data['frequency_id']) && $data['frequency_id'] != '') {
            return $data['frequency_id'];
        }
        return null;
    }

    /**
     * Check category exit on database
     *
     * @param int $categoryId
     * @return bool
     */
    public function checkCategoryExit($categoryId)
    {
        $categoryId = (int)$categoryId;
        $connection = $this->resourceConnection->getConnection('default');
        $select = $connection->select()
            ->from([$this->getResource()->getTable('catalog_category_entity')])
            ->where("entity_id =$categoryId");
        $data = $connection->fetchRow($select);
        if (isset($data['entity_id']) && $data['entity_id'] != '') {
            return true;
        }
        return false;
    }

    /**
     * check website ID
     * @param $websiteId
     * @return bool
     */
    public function checkWebSiteId($websiteId)
    {
        $websiteId = (int)$websiteId;
        $connection = $this->resourceConnection->getConnection('default');
        $select = $connection->select()
            ->from([$this->getResource()->getTable('store_website')])
            ->where("website_id =$websiteId");
        $data = $connection->fetchRow($select);
        if (isset($data['website_id']) && $data['website_id'] != '') {
            return true;
        }
        return false;
    }

    public function checkPaymnetId($paymentId)
    {
        $paymentId = (int)$paymentId;
        $connection = $this->resourceConnection->getConnection('sales');
        $select = $connection->select()
            ->from([$this->getResource()->getTable('payment_fee')])
            ->where("entity_id =$paymentId");
        $data = $connection->fetchRow($select);
        if (isset($data['entity_id']) && $data['entity_id'] != '') {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    protected function _validateSequenceProducts()
    {
        $errors = [];
        $hanpukaiMaximumOrderTimes = (int)$this->getHanpukaiMaximumOrderTimes();
        $arrHanpukaiDeliveryProductConfig = [];
        if ($this->getProducts()) {
            $hasFirstDelivery = false;
            foreach ($this->getProducts() as $productId => $data) {
                if (!in_array((int)$data['delivery_number'], $arrHanpukaiDeliveryProductConfig)) {
                    $arrHanpukaiDeliveryProductConfig[] = (int)$data['delivery_number'];
                }

                if ((int)$this->getHanpukaiMaximumOrderTimes() && (int)$data['delivery_number'] > $hanpukaiMaximumOrderTimes) {
                    $errors[] = __('Delivery number is invalid for product #%1, maximum order times is %2', $productId, (int)$this->getHanpukaiMaximumOrderTimes());
                }

                if ((int)$data['delivery_number'] <= 1) {
                    $hasFirstDelivery = true;
                }
            }

            if (!$hasFirstDelivery) {
                $errors[] = __('Please select product(s) for the first delivery');
            }

            for ($i = 1; $i <= $hanpukaiMaximumOrderTimes; $i++) {
                if (!in_array($i, $arrHanpukaiDeliveryProductConfig)) {
                    $errors[] = __('Please config product for delivery number #%1', $i);
                }
            }
        }

        return $errors;
    }


    /**
     * check course's product is match the promotion rule
     *
     * @param int $item product id
     * @param int $rule rule id
     * @param int $frequency frequency id
     * @return boolean
     */
    public function isMatchPromotionRule($item, $rule, $frequency)
    {
        $subscriptionType = $this->getData('subscription_type');
        if ($subscriptionType == SubscriptionType::TYPE_HANPUKAI) {
            $countProduct = $this->getResource()->getProductCourseByRule($item, $rule, $this->getId(), $frequency);
            return $countProduct;
        } else {
            $countCategoryProduct = $this->getResource()->getCategoryProductCourseByRule($item, $rule, $this->getId(), $frequency);
            return $countCategoryProduct;
        }
    }

    /**
     * get payment method allowed of a course
     */
    public function getAllowPaymentMethod()
    {
        $allowPaymentMethodIds = $this->getResource()->getPaymentIds($this->getId());
        $listPayments = [];
        foreach ($allowPaymentMethodIds as $id) {
            $listPayments[] = $this->mapPaymentMethod($id);
        }
        return $listPayments;
    }

    /**
     * get course frequency list
     * @return array
     */
    public function getCourseFrequencyList()
    {
        $courses = $this->getResource()->getAllCourses();
        $options = [];
        foreach ($courses as $course) {
            $options[$course['course_id']] = $this->getResource()->getFrequencyIds($course['course_id']);
        }
        return $options;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSettings()
    {
        return [
            'is_allow_skip_next_delivery' => $this->getData('allow_skip_next_delivery'),
            'is_allow_change_next_delivery' => $this->getData('allow_change_next_delivery_date'),
            'is_allow_change_payment_method' => $this->getData('allow_change_payment_method'),
            'is_allow_change_address' => $this->getData('allow_change_address'),
            'is_allow_change_product' => $this->getData('allow_change_product'),
            'is_allow_change_qty' => $this->getData('allow_change_qty'),
            'hanpukai_delivery_date_allowed' => $this->getData('hanpukai_delivery_date_allowed'),
            'hanpukai_delivery_date_from' => $this->getData('hanpukai_delivery_date_from'),
            'hanpukai_delivery_date_to' => $this->getData('hanpukai_delivery_date_to'),
            'next_delivery_date_calculation_option' => $this->getData('next_delivery_date_calculation_option')
        ];
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isAllowChangeNextDeliveryDate()
    {
        $settings = $this->getSettings();
        return $settings['is_allow_change_next_delivery'];
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isAllowChooseDeliveryDate()
    {
        $disableChangeDeliveryDate = $this->deliveryDateSelectionConfig->getDisableChangeDeliveryDateConfig();
        if ($disableChangeDeliveryDate) {
            $allowChooseDeliveryDate = 0;
        } else {
            $allowChooseDeliveryDate = $this->getData('allow_choose_delivery_date');
        }
        return $allowChooseDeliveryDate;
    }

    public function getAssociatedEntity($entity, $data)
    {
        return $this->getResource()->getAssociatedEntity($this->getId(), $entity, $data);
    }

    /**
     * this object is Hanpukai?
     *
     * @return bool
     */
    public function isHanpukai()
    {
        return $this->getSubscriptionType() == SubscriptionType::TYPE_HANPUKAI;
    }

    /**
     * this object is multi machines?
     *
     * @return bool
     */
    public function isMachine()
    {
        return $this->getSubscriptionType() == SubscriptionType::TYPE_MULTI_MACHINES;
    }

    /**
     *
     * @return array|mixed
     */
    public function getHanpukaiFixedProductsData()
    {
        if (!$this->getId()
            || !$this->isHanpukai()
            || $this->getHanpukaiType() != SubscriptionType::TYPE_HANPUKAI_FIXED
        ) {
            return [];
        }

        $array = $this->getData('hanpukai_fixed_products_data');
        if ($array === null) {
            $array = $this->getResource()->getHanpukaiFixedProductsData($this);
            $this->setData('hanpukai_fixed_products_data', $array);
        }
        return $array;
    }

    public function getListMachineType()
    {
        if (!$this->getId() || !$this->isMultipleMachine()) {
            return [];
        }
        return $this->getResource()->getListMachineType($this);
    }

    /**
     *
     * @return array|mixed
     */
    public function getHanpukaiFixedProductsDataPieceCase()
    {
        if (!$this->getId()
            || !$this->isHanpukai()
            || $this->getHanpukaiType() != SubscriptionType::TYPE_HANPUKAI_FIXED
        ) {
            return [];
        }

        $array = $this->getData('hanpukai_fixed_products_data');
        if ($array === null) {
            $array = $this->getResource()->getHanpukaiFixedProductsDataPieCase($this);
            $this->setData('hanpukai_fixed_products_data', $array);
        }
        return $array;
    }

    /**
     *
     * @return array|mixed
     */
    public function getHanpukaiSequenceProductsData()
    {
        if (!$this->getId()
            || !$this->isHanpukai()
            || $this->getHanpukaiType() != SubscriptionType::TYPE_HANPUKAI_SEQUENCE
        ) {
            return [];
        }

        $array = $this->getData('hanpukai_sequence_products_data');
        if ($array === null) {
            $array = $this->getResource()->getHanpukaiSequenceProductsData($this);
            $this->setData('hanpukai_sequence_products_data', $array);
        }
        return $array;
    }

    public function replaceProduct($oldId, $newId)
    {
        return $this->getResource()->replaceProduct($oldId, $newId);
    }

    public function replaceProductInCategory($oldId, $newId)
    {
        return $this->getResource()->replaceProductInCategory($oldId, $newId);
    }

    public function deleteProductInCategory($producId)
    {
        return $this->getResource()->deleteProductInCategory($producId);
    }

    /**
     * Get Machine products By Course
     *
     * @param int $courseId
     *
     * @return array
     */
    public function getMachinesByCourse($courseId)
    {
        return $this->getResource()->getMachinesByCourse($courseId);
    }

    /**
     * Get Machine products By Course
     *
     * @return array|bool
     */
    public function getProductMachines()
    {
        if ($this->getId()) {
            return $this->getMachinesByCourse($this->getId());
        }
        return false;
    }

    /**
     * Get Machine product
     *
     * @param int $courseId
     * @param int $productId
     *
     * @return array
     */
    public function getMachine($courseId, $productId)
    {
        return $this->getResource()->getMachine($courseId, $productId);
    }

    /**
     * @inheritdoc
     */
    public function setNthDeliverySimulation($nth)
    {
        return $this->setData(self::NTH_DELIVERY_SIMULATION, $nth);
    }

    /**
     * @inheritdoc
     */
    public function getNthDeliverySimulation()
    {
        return $this->getData(self::NTH_DELIVERY_SIMULATION);
    }

    /**
     * @return mixed
     */
    public function getHanpukaiMaximumOrderTimes()
    {
        return $this->getData(self::HANPUKAI_MAXIMUM_ORDER_TIMES);
    }

    /**
     * @return mixed
     */
    public function isDelayPayment()
    {
        return $this->getData(self::IS_DELAY_PAYMENT);
    }

    public function isMultipleMachine()
    {
        if ($this->getData('subscription_type') == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
            return true;
        }
        return false;
    }

    /**
     * @return int|null
     */
    public function isShoppingPointDeduction()
    {
        return $this->getData(self::IS_SHOPPING_POINT_DEDUCTION);
    }

    /**
     * @return int|null
     */
    public function getCapturedAmountCalculationOption()
    {
        return $this->getData(self::CAPTURED_AMOUNT_CALCULATION_OPTION);
    }

    /**
     * Get next delivery date calculation option
     *
     * @return array
     */
    public function getNextDeliveryDateCalculationOption()
    {
        return [
            self::NEXT_DELIVERY_DATE_CALCULATION_OPTION_DAY_OF_MONTH => __('Day of Month'),
            self::NEXT_DELIVERY_DATE_CALCULATION_OPTION_DAY_OF_WEEK => __('Day of Week')
        ];
    }

    /**
     * Get order total amount options
     * @return array
     */
    public function getOrderTotalAmountOptions()
    {
        return [
            self::TOTAL_AMOUNT_OPTION_SECOND_ORDER => __('Only apply for the second order'),
            self::TOTAL_AMOUNT_OPTION_ALL_ORDER => __('Apply for all orders')
        ];
    }

    /**
     * @inheritdoc
     */
    public function setOrderTotalAmountOption($orderTotalAmountOption)
    {
        return $this->setData(self::ORDER_TOTAL_AMOUNT_OPTION, $orderTotalAmountOption);
    }

    /**
     * @inheritdoc
     */
    public function getOrderTotalAmountOption()
    {
        return $this->getData(self::ORDER_TOTAL_AMOUNT_OPTION);
    }

    /**
     * @inheritdoc
     */
    public function setTermsOfUse($termsOfUse)
    {
        return $this->setData(self::TERMS_OF_USE, $termsOfUse);
    }

    public function getTermsOfUse()
    {
        return $this->getData(self::TERMS_OF_USE);
    }
}
