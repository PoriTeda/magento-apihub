<?php

namespace Riki\SubscriptionCourse\Model\ImportHandler;

use Magento\Framework\App\ResourceConnection;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

class SubscriptionCourse extends \Magento\ImportExport\Model\Import\Entity\AbstractEntity
{

    const MESSAGE_ERROR_1 = 'Value for \'%s\' attribute contains incorrect value, ';

    const MESSAGE_ERROR_2 = 'see acceptable values on settings specified for Admin.';

    const FOLDER_NAME = 'subscription_course_import';

    const FILE_NAME = 'subscription_course.csv';

    /**
     * Column product sku.
     */
    const COL_COURSE_CODE = 'course_code';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $messageTemplates = [
        RowValidatorInterface::ERROR_VALUE_IS_REQUIRED => '%s is required field.',
        RowValidatorInterface::ERROR_EXCEEDED_MAX_LENGTH => '%s is longer than %s characters.',
        RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_OPTION => self::MESSAGE_ERROR_1 . self::MESSAGE_ERROR_2,
        RowValidatorInterface::ERROR_INVALID_DATE_FORMAT => '%s must be in format Y-m-d H:m:s.',
        RowValidatorInterface::ERROR_INVALID_WBS_FORMAT => '"%s" is not WBS format.',
        RowValidatorInterface::ERROR_DUPLICATE_UNIQUE_ATTRIBUTE => 'Duplicated unique attribute.',
        RowValidatorInterface::ERROR_INVALID_CATEGORY_ID => 'Category Id %s is not found.',
        RowValidatorInterface::ERROR_INVALID_COURSE_ID => 'Course Id %s is not found or invalid.',
        RowValidatorInterface::ERROR_INVALID_B2C_MACHINES_ID => 'B2C Machine Type Id %s is not found or invalid.',
        RowValidatorInterface::ERROR_JSON_KEY_NOT_FOUND => 'Json key %s is not found.',
        RowValidatorInterface::ERROR_INVALID_JSON_FORMAT => 'Can not parse json %s.',
        RowValidatorInterface::ERROR_MEMBERSHIP_NOT_FOUND => 'Membership Id %s is not found.',
        RowValidatorInterface::ERROR_FREQUENCY_NOT_FOUND => 'Frequency Id %s is not found.',
        RowValidatorInterface::ERROR_PAYMENT_NOT_FOUND => 'Payment Id %s is not found or disable.',
        RowValidatorInterface::ERROR_WEBSITE_NOT_FOUND => 'Website Id %s is not found.',
        RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_TYPE => '%s is not %s data type.',
        RowValidatorInterface::ERROR_VALUE_IS_POSITIVE_NUMBER => '%s must be a positive value.',
        RowValidatorInterface::ERROR_MAIN_CATEGORY_NOT_FOUND => '%s is not found or empty.',

    ];

    protected $fieldsProperties = [
        'course_name' => [
            'type' => 'varchar',
            'len' => 255,
            'is_required' => true,
        ],
        'course_code' => [
            'type' => 'varchar',
            'len' => 20,
            'is_required' => true,
            'is_unique' => true
        ],
        'duration_unit' => [
            'type' => 'select',
            'options' => ['week', 'month'],
        ],
        'duration_interval' => [
            'type' => 'smallint',
            'unsigned' => true
        ],
        'must_select_sku' => [
            'type' => 'varchar',
            'len' => 255
        ],
        'minimum_order_qty' => [
            'type' => 'smallint',
            'unsigned' => true
        ],
        'minimum_order_times' => [
            'type' => 'smallint',
            'unsigned' => true
        ],
        'sales_count' => [
            'type' => 'int',
            'unsigned' => true
        ],
        'application_count' => [
            'type' => 'int',
            'unsigned' => true
        ],
        'application_limit' => [
            'type' => 'int',
            'unsigned' => true
        ],
        'applied_payment_method_code' => [
            'type' => 'text',
            'len' => 255
        ],
        'membership_type_restriction' => [
            'type' => 'smallint',
            'unsigned' => true
        ],
        'description' => [
            'type' => 'text',
        ],
        'is_enable' => [
            'type' => 'select',
            'options' => [0, 1],
            'is_required' => true
        ],
        'allow_skip_next_delivery' => [
            'type' => 'select',
            'options' => [0, 1],
        ],
        'launch_date' => [
            'type' => 'date',
            'is_required' => true
        ],
        'close_date' => [
            'type' => 'date',
        ],
        'meta_title' => [
            'type' => 'varchar',
            'len' => 255
        ],
        'meta_keywords' => [
            'type' => 'text',
        ],
        'meta_description' => [
            'type' => 'text',
        ],
        'penalty_fee' => [
            'type' => 'decimal',
        ],
        'allow_change_next_delivery_date' => [
            'type' => 'select',
            'options' => [0, 1],
        ],
        'allow_change_payment_method' => [
            'type' => 'select',
            'options' => [0, 1],
        ],
        'allow_change_address' => [
            'type' => 'select',
            'options' => [0, 1],
        ],
        'allow_change_product' => [
            'type' => 'select',
            'options' => [0, 1],
        ],
        'allow_change_qty' => [
            'type' => 'select',
            'options' => [0, 1],
        ],
        'sales_value_count' => [
            'type' => 'decimal',
        ],
        'visibility' => [
            'type' => 'select',
            'options' => [0, 1, 2, 3],
        ],
        'subscription_type' => [
            'type' => 'select',
            'options' => ['subscription', 'hanpukai', 'multimachine'],
        ],
        'hanpukai_type' => [
            'type' => 'select',
            'options' => ['hfixed', 'hsequence'],
        ],
        'hanpukai_maximum_order_times' => [
            'type' => 'int',
        ],
        'hanpukai_delivery_date_allowed' => [
            'type' => 'select',
            'options' => [0, 1],
        ],
        'hanpukai_delivery_date_from' => [
            'type' => 'date',
        ],
        'hanpukai_delivery_date_to' => [
            'type' => 'date',
        ],
        'hanpukai_first_delivery_date' => [
            'type' => 'date',
        ],
        'navigation_path' => [
            'type' => 'varchar',
            'len' => 255
        ],
        'design' => [
            'type' => 'varchar',
            'len' => 255
        ],
        'additional_category_description' => [
            'type' => 'text',
        ],
        'point_for_trial' => [
            'type' => 'int',
        ],
        'point_for_trial_wbs' => [
            'type' => 'wbs',
            'len' => 255
        ],
        'point_for_trial_account_code' => [
            'type' => 'varchar',
            'len' => 255
        ],
        'nth_delivery_simulation' => [
            'type' => 'smallint',
        ],
        'is_delay_payment' => [
            'type' => 'select',
            'options' => [0, 1],
            'is_required' => true
        ],
        'maximum_order_qty' => [
            'type' => 'smallint',
        ],
        'captured_amount_calculation_option' => [
            'type' => 'select',
            'options' => [0, 1],
        ],
        'is_shopping_point_deduction' => [
            'type' => 'select',
            'options' => [0, 1],
        ],
        'payment_delay_time' => [
            'type' => 'int',
        ],
        'subscription_course_category' => [
            'type' => 'json',
            'is_required' => true
        ],
        'subscription_course_frequency' => [
            'type' => 'json',
            'is_required' => true
        ],
        'subscription_course_membership' => [
            'type' => 'json',
            'is_required' => true
        ],
        'subscription_course_merge_profile' => [
            'type' => 'json',
        ],
        'subscription_course_payment' => [
            'type' => 'json',
            'is_required' => true
        ],
        'subscription_course_website' => [
            'type' => 'json',
            'is_required' => true
        ],
        'multiple_machine' => [
            'type' => 'json',
            'is_required' => false
        ],
    ];

    protected $permanentAttributes = [
        'course_id',
        'course_name',
        'course_code',
        'duration_unit',
        'duration_interval',
        'must_select_sku',
        'minimum_order_qty',
        'minimum_order_times',
        'sales_count',
        'application_count',
        'application_limit',
        'applied_payment_method_code',
        'membership_type_restriction',
        'description',
        'is_enable',
        'allow_skip_next_delivery',
        'launch_date',
        'close_date',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'penalty_fee',
        'allow_change_next_delivery_date',
        'allow_change_payment_method',
        'allow_change_address',
        'allow_change_product',
        'allow_change_qty',
        'sales_value_count',
        'visibility',
        'subscription_type',
        'hanpukai_type',
        'hanpukai_maximum_order_times',
        'hanpukai_delivery_date_allowed',
        'hanpukai_delivery_date_from',
        'hanpukai_delivery_date_to',
        'hanpukai_first_delivery_date',
        'navigation_path',
        'design',
        'additional_category_description',
        'point_for_trial',
        'point_for_trial_wbs',
        'point_for_trial_account_code',
        'nth_delivery_simulation',
        'is_delay_payment',
        'maximum_order_qty',
        'captured_amount_calculation_option',
        'is_shopping_point_deduction',
        'payment_delay_time',
        'subscription_course_category',
        'subscription_course_frequency',
        'subscription_course_membership',
        'subscription_course_merge_profile',
        'subscription_course_payment',
        'subscription_course_website',
    ];

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * SubscriptionCourse constructor.
     *
     * @param  \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param  \Magento\ImportExport\Helper\Data $importExportData
     * @param  \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param  \Magento\Eav\Model\Config $config
     * @param  ResourceConnection $resource
     * @param  \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param  \Magento\Framework\Stdlib\StringUtils $string
     * @param  ProcessingErrorAggregatorInterface $errorAggregator
     * @param  Validator $validator
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Eav\Model\Config $config,
        ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Stdlib\StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        \Riki\SubscriptionCourse\Model\ImportHandler\Validator $validator
    ) {
        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator
        );
        $this->validator = $validator;
        $this->_messageTemplates = $this->messageTemplates;
    }

    /**
     * Check one attribute. Can be overridden in child.
     *
     * @param  string $attrCode Attribute code
     * @param  array $attrParams Attribute params
     * @param  array $rowData Row data
     * @param  int $rowNum
     * @return bool
     */
    public function isAttributeValid($attrCode, array $attrParams, array $rowData, $rowNum)
    {
        if (!$this->getvalidator()->isAttributeValid($attrCode, $attrParams, $rowData)) {
            foreach ($this->getvalidator()->getMessages() as $message) {
                $this->addRowError($message, $rowNum, $attrCode);
            }
            return false;
        }
        return true;
    }

    private function getvalidator()
    {
        $this->validator->init($this);
        return $this->validator;
    }

    /**
     * Validate data row.
     *
     * @param  array $rowData
     * @param  int $rowNum
     * @return bool
     * @throws \Zend_Validate_Exception
     */
    public function validateRow(array $rowData, $rowNum)
    {
        if (isset($this->_validatedRows[$rowNum])) {
            // check that row is already validated
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;

        if (!$this->getvalidator()->isValid($rowData)) {
            foreach ($this->getvalidator()->getMessages() as $message) {
                $this->addRowError($message, $rowNum);
            }
        }

        // SKU is specified, row is SCOPE_DEFAULT, new product block begins
        $this->_processedEntitiesCount++;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Validate data rows and save bunches to DB
     *
     * @return $this|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $source->rewind();
        while ($source->valid()) {
            try {
                $rowData = $source->current();
            } catch (\InvalidArgumentException $e) {
                $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                $this->_processedRowsCount++;
                $source->next();
                continue;
            }

            $this->validateRow($rowData, $source->key());
            $source->next();
        }

        return parent::_saveValidatedBunches();
    }

    /**
     * @param $code
     * @return bool|mixed
     */
    public function getAttributeProperties($code)
    {
        if (isset($this->fieldsProperties[$code])) {
            return $this->fieldsProperties[$code];
        }

        return false;
    }

    /**
     * Import data rows.
     *
     * @return bool
     */
    protected function _importData()
    {
        return true;
    }

    /**
     * EAV entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'order';
    }

    /**
     * @return ProcessingErrorAggregatorInterface
     */
    public function getErrorAggregator()
    {
        foreach ($this->errorMessageTemplates as $errorCode => $message) {
            $this->errorAggregator->addErrorMessageTemplate($errorCode, $message);
        }
        return $this->errorAggregator;
    }
}
