<?php

namespace Riki\SubscriptionMachine\Model\MonthlyFeeProfile;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Riki\SubscriptionCourse\Helper\Data;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;
use Riki\SubscriptionMachine\Exception\InputException;

class Validator
{
    const VALIDATION_RULES_CREATE_PROFILE = [
        'required' => [
            'error_code' => 2001,
            'fields' => [
                'consumerdb_customer_id',
                'next_delivery_date',
                'subscription_course_code',
                'frequency_interval',
                'frequency_unit'
            ],
            'products' => [
                'qty',
                'sku'
            ]
        ],
        'type' => [
            'error_code' => 2002,
            'fields' => [
                'date' => [
                    'next_order_date',
                    'next_delivery_date'
                ]
            ]
        ],
        'must_exist' => [
            'error_code' => 2003,
            'fields' => [
                'consumerdb_customer_id',
                'subscription_course_code',
                'reference_profile_id'
            ],
            'products' => [
                'sku',
            ]
        ],
        'other' => [
            'error_code' => 2004,
            'min_value' => [
                'fields' => [
                    'variable_fee' => 1,
                    'frequency_interval' => 1,
                    'next_delivery_date' => 'current_date',
                    'next_order_date' => 'next_delivery_date'
                ]
            ],
            'products' => [
                'min_value' => [
                    'qty' => 1
                ]
            ]
        ]
    ];

    const VALIDATION_RULES_UPDATE_PROFILE = [
        'required' => [
            'error_code' => 2001,
            'fields' => [
                'profile_id',
                'next_delivery_date'
            ],
            'products' => [
                'qty',
                'sku'
            ]
        ],
        'type' => [
            'error_code' => 2002,
            'fields' => [
                'date' => [
                    'next_order_date',
                    'next_delivery_date'
                ]
            ]
        ],
        'must_exist' => [
            'error_code' => 2003,
            'fields' => [
                'profile_id'
            ],
            'products' => [
                'sku'
            ]
        ],
        'other' => [
            'error_code' => 2004,
            'min_value' => [
                'fields' => [
                    'variable_fee' => 1,
                    'next_delivery_date' => 'current_date',
                    'next_order_date' => 'next_delivery_date'
                ]
            ],
            'products' => [
                'min_value' => [
                    'qty' => 1
                ]
            ]
        ]
    ];

    const VALIDATION_RULES_DISENGAGE_PROFILE = [
        'required' => [
            'error_code' => 2001,
            'fields' => [
                'profile_id',
                'disengagement_user',
                'disengagement_date',
                'disengagement_reasons'
            ]
        ],
        'type' => [
            'error_code' => 2002,
            'fields' => [
                'integer' => [
                    'profile_id'
                ],
                'date' => [
                    'disengagement_date'
                ]
            ]
        ],
        'must_exist' => [
            'error_code' => 2003,
            'fields' => [
                'profile_id',
                'disengagement_reasons'
            ]
        ],
        'other' => [
            'error_code' => 2004,
            'min_value' => [
                'fields' => [
                    'disengagement_date' => 'current_date'
                ]
            ],
            'max_length' => [
                'fields' => [
                    'disengagement_user' => 255
                ]
            ],
        ]
    ];

    const VALIDATION_RULES_COFFEE_SUBSCRIPTION_ORDER_APPROVE = [
        'required' => [
            'error_code' => 2001,
            'fields' => [
                'consumerdb_customer_id',
            ]
        ],
        'must_exist' => [
            'error_code' => 2003,
            'fields' => [
                'consumerdb_customer_id'
            ]
        ],
    ];

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $subscriptionCourseRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $timezone;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var Data
     */
    protected $courseHelper;

    /**
     * @var \Riki\SubscriptionProfileDisengagement\Model\Config\Source\Reason
     */
    protected $disengagementReasonSource;

    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\CourseFactory
     */
    protected $courseFactory;

    /**
     * ValidateData constructor.
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $subscriptionCourseRepository
     * @param Data $courseHelper
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Riki\SubscriptionProfileDisengagement\Model\Config\Source\Reason $disengagementReasonSource
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\CourseFactory $courseFactory
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $subscriptionCourseRepository,
        \Riki\SubscriptionCourse\Helper\Data $courseHelper,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Riki\SubscriptionProfileDisengagement\Model\Config\Source\Reason $disengagementReasonSource,
        \Riki\SubscriptionCourse\Model\ResourceModel\CourseFactory $courseFactory
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->customerRepository = $customerRepository;
        $this->profileRepository = $profileRepository;
        $this->subscriptionCourseRepository = $subscriptionCourseRepository;
        $this->productRepository = $productRepository;
        $this->timezone = $timezone;
        $this->courseHelper = $courseHelper;
        $this->disengagementReasonSource = $disengagementReasonSource;
        $this->courseFactory = $courseFactory;
    }

    /**
     * Validate data for create profile
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileCreationInterface $monthlyFeeProfile
     * @return $this
     * @throws \Riki\SubscriptionMachine\Exception\InputException InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function validateCreationRules($monthlyFeeProfile)
    {
        $this->_validate(self::VALIDATION_RULES_CREATE_PROFILE, $monthlyFeeProfile);
        return $this;
    }

    /**
     * Validate data for update profile
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileUpdateInterface $monthlyFeeProfile
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Riki\SubscriptionMachine\Exception\InputException
     * @throws \Zend_Validate_Exception
     */
    public function validateUpdateRules($monthlyFeeProfile)
    {
        $this->_validate(self::VALIDATION_RULES_UPDATE_PROFILE, $monthlyFeeProfile);
        return $this;
    }

    /**
     * Validate data for update profile
     * @param \Riki\SubscriptionMachine\Api\Data\DisengagementProfileInterface $disengagementProfile
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Riki\SubscriptionMachine\Exception\InputException
     * @throws \Zend_Validate_Exception
     */
    public function validateDisengageRules($disengagementProfile)
    {
        $this->_validate(self::VALIDATION_RULES_DISENGAGE_PROFILE, $disengagementProfile);
        return $this;
    }

    /**
     * Validate data for approving coffee subscription order
     * @param mixed $consumerObject
     * @return $this
     * @throws InputException
     * @throws LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function validateApproveRules($consumerObject)
    {
        $this->_validate(self::VALIDATION_RULES_COFFEE_SUBSCRIPTION_ORDER_APPROVE, $consumerObject);
        return $this;
    }

    /**
     * @param array $ruleGroup
     * @param mixed $profile
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Riki\SubscriptionMachine\Exception\InputException
     * @throws \Zend_Validate_Exception
     */
    private function _validate(array $ruleGroup, $profile)
    {
        foreach ($ruleGroup as $key => $rules) {
            switch ($key) {
                case 'required':
                    $this->_validateRequired($rules, $profile);
                    break;
                case 'type':
                    $this->_validateType($rules, $profile);
                    break;
                case 'must_exist':
                    $this->_validateExistence($rules, $profile);
                    break;
                case 'other':
                    $this->_validateOther($rules, $profile);
                    break;
                default:
                    throw new LocalizedException(__('Invalid %ruleGroup', ['ruleGroup' => $key]));
            }
        }
    }

    /**
     * Validate required filed
     * @param array $rules
     * @param mixed $profile
     * @throws InputException
     * @throws LocalizedException
     * @throws \Zend_Validate_Exception
     */
    private function _validateRequired($rules, $profile)
    {
        if (!empty($rules) && isset($rules['error_code'])) {
            // phpcs:ignore MEQP2.Classes.ObjectInstantiation
            $inputException = new InputException();
            if (isset($rules['fields'])) {
                foreach ($rules['fields'] as $key => $field) {
                    if (!\Zend_Validate::is($profile->getData($field), 'NotEmpty')) {
                        $inputException->addError($this->getErrorMessage('required', $field));
                    }
                }
            }

            /**
             * Validate value for all item of product
             */
            if (isset($rules['products'])) {
                $products = $profile->getProducts();
                /**
                 * Doest not request value
                 */
                if (empty($products)) {
                    foreach ($rules['products'] as $field) {
                        $inputException->addError($this->getErrorMessage(InputException::ERROR_TYPE_REQUIRED, $field));
                    }
                } else {
                    /**
                     * Validate value for all item of product
                     */
                    $flag = true;
                    foreach ($products as $product) {
                        foreach ($rules['products'] as $field) {
                            if (!\Zend_Validate::is($product->getData($field), 'NotEmpty')) {
                                $inputException->addError($this->getErrorMessage(
                                    InputException::ERROR_TYPE_REQUIRED,
                                    $field
                                ));
                            }
                        }

                        if (!$flag) {
                            break;
                        }
                    }
                }
            }

            if ($inputException->wasErrorAdded()) {
                $errorCode = $rules['error_code'];
                $inputException->setErrorCode($errorCode);
                throw $inputException;
            }
        }
    }

    /**
     * Validate type
     * @param array $rules
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileUpdateInterface $profile
     * @throws InputException
     * @throws LocalizedException
     * @throws \Zend_Validate_Exception
     */
    private function _validateType($rules, $profile)
    {
        if (!empty($rules) && isset($rules['error_code'])) {
            // phpcs:ignore MEQP2.Classes.ObjectInstantiation
            $inputException = new InputException();
            if (isset($rules['fields'])) {
                foreach ($rules['fields'] as $key => $fields) {
                    switch ($key) {
                        case 'integer':
                            $this->_validateDataType('Int', $fields, $profile, $inputException);
                            break;
                        case 'float':
                            $this->_validateDataType('Float', $fields, $profile, $inputException);
                            break;
                        case 'date':
                            $this->_validateDataType('Date', $fields, $profile, $inputException);
                            break;
                        default:
                            throw new LocalizedException(__('Invalid %fieldType', ['fieldType' => $key]));
                    }
                }
            }

            if ($inputException->wasErrorAdded()) {
                $errorCode = $rules['error_code'];
                $inputException->setErrorCode($errorCode);
                throw $inputException;
            }
        }
    }

    /**
     * Validate must exist
     * @param array $rules
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileUpdateInterface $profile
     * @throws InputException
     * @throws LocalizedException
     */
    public function _validateExistence($rules, $profile)
    {
        // phpcs:ignore MEQP2.Classes.ObjectInstantiation
        $inputException = new InputException();
        $errorType = InputException::ERROR_TYPE_MUST_EXIST;
        foreach ($rules['fields'] as $key => $field) {
            switch ($field) {
                case 'consumerdb_customer_id':
                    $consumerDbId = $profile->getData($field);
                    $customer = $this->getCustomerByConsumerDbId($consumerDbId);
                    if (!$customer) {
                        $inputException->addError(
                            $this->getErrorMessage($errorType, $field, ['value' => $consumerDbId])
                        );
                    }
                    break;
                case 'subscription_course_code':
                    $subscriptionCode = $profile->getData($field);
                    $course = $this->getSubscriptionCourse($subscriptionCode);
                    if (!$course) {
                        $inputException->addError(
                            $this->getErrorMessage($errorType, $field, ['value' => $subscriptionCode])
                        );
                    }
                    break;
                case 'reference_profile_id':
                    $referenceProfileId = $profile->getReferenceProfileId();
                    if ($referenceProfileId !== null) {
                        $profileModel = $this->getProfileById($referenceProfileId);
                        if (!$profileModel) {
                            $inputException->addError(
                                $this->getErrorMessage($errorType, $field, ['value' => $referenceProfileId])
                            );
                        }
                    }
                    break;
                case 'profile_id':
                    $profileId = $profile->getData('profile_id');
                    $profileModel = $this->getProfileById($profileId);
                    if (!$profileModel) {
                        $inputException->addError(
                            $this->getErrorMessage($errorType, $field, ['value' => $profileId])
                        );
                    }
                    break;
                case 'disengagement_reasons':
                    $reasonCodes = $this->disengagementReasonSource->codeToTitle();
                    foreach ($profile->getData($field) as $value) {
                        if (!array_key_exists($value, $reasonCodes)) {
                            $inputException->addError(
                                $this->getErrorMessage($errorType, $field, ['value' => $value])
                            );
                            break;
                        }
                    }
                    break;

                default:
                    //do not thing
                    break;
            }
        }

        /**
         * Validate value for all item of product
         */
        if (isset($rules['products'])) {
            $products = $profile->getProducts();
            foreach ($products as $item) {
                foreach ($rules['products'] as $key => $field) {
                    $sku = $item->getData($field);
                    try {
                        $this->productRepository->get($sku);
                    } catch (NoSuchEntityException $e) {
                        $inputException->addError(
                            $this->getErrorMessage($errorType, $field, ['value' => $sku])
                        );
                    }
                }
            }
        }

        if ($inputException->wasErrorAdded()) {
            $errorCode = $rules['error_code'];
            $inputException->setErrorCode($errorCode);
            throw $inputException;
        }
    }

    /**
     * Validate other
     *
     * @param array $rules
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileUpdateInterface $profile
     * @throws InputException
     * @throws LocalizedException
     */
    public function _validateOther($rules, $profile)
    {
        if (!empty($rules) && isset($rules['error_code'])) {
            // phpcs:ignore MEQP2.Classes.ObjectInstantiation
            $inputException = new InputException();

            /**
             * Validate min value
             */
            $this->_validateMinValue($rules, $profile, $inputException);

            /**
             * Validate max length
             */
             $this->_validateMaxLength($rules, $profile, $inputException);

            /**
             * Validate value for all item of product
             */
            if (isset($rules['products'])) {
                $products = $profile->getProducts();
                $flag = true;
                foreach ($products as $product) {
                    foreach ($rules['products'] as $key => $fields) {
                        foreach ($fields as $field => $minValue) {
                            $qty = $product->getData($field);
                            if ((int)$qty < $minValue) {
                                $values = ['value' => $qty, 'minValue' => $minValue];
                                $inputException->addError(
                                    $this->getErrorMessage(InputException::ERROR_TYPE_MIN_VALUE, $field, $values)
                                );
                                $flag = false;
                            }
                        }
                    }
                    if (!$flag) {
                        break;
                    }
                }
            }

            if ($inputException->wasErrorAdded()) {
                $errorCode = $rules['error_code'];
                $inputException->setErrorCode($errorCode);
                throw $inputException;
            }
        }
    }

    /**
     * @param array $rules
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileUpdateInterface $profile
     * @throws $inputException
     * @throws LocalizedException
     */
    private function _validateMinValue($rules, $profile, &$inputException)
    {
        if (isset($rules['min_value']) && !empty($rules['min_value']['fields'])) {
            foreach ($rules['min_value']['fields'] as $field => $minValue) {
                switch ($field) {
                    case 'disengagement_date':
                    case 'next_delivery_date':
                        $currentDate = $this->timezone->date()->format('Y-m-d');
                        $nextDeliveryDate = $profile->getData($field);
                        if (strtotime($currentDate) > strtotime($nextDeliveryDate)) {
                            $inputException->addError(
                                $this->getErrorMessage(
                                    InputException::ERROR_TYPE_MIN_VALUE,
                                    $field,
                                    ['value' => $nextDeliveryDate, 'minValue' => $currentDate]
                                )
                            );
                        }
                        break;
                    case 'next_order_date':
                        $nextDeliveryDate = $profile->getData('next_delivery_date');
                        $nextOrderDate = $profile->getData($field);
                        if (strtotime($nextDeliveryDate) < strtotime($nextOrderDate)) {
                            $inputException->addError(
                                $this->getErrorMessage(
                                    InputException::ERROR_TYPE_MIN_VALUE,
                                    $field,
                                    ['value' => $nextOrderDate, 'minValue' => $nextDeliveryDate]
                                )
                            );
                        }
                        break;
                    case 'variable_fee':
                    case 'frequency_interval':
                        $value = $profile->getData($field);
                        if ($value !== null && $value < $minValue) {
                            $inputException->addError(
                                $this->getErrorMessage(
                                    InputException::ERROR_TYPE_MIN_VALUE,
                                    $field,
                                    ['value' => $value, 'minValue' => $minValue]
                                )
                            );
                        }
                        break;
                    default:
                        //do not thing
                        break;
                }
            }
        }
    }

    /**
     * @param array $rules
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileUpdateInterface $profile
     * @throws $inputException
     * @throws LocalizedException
     */
    private function _validateMaxLength($rules, $profile, &$inputException)
    {
        if (isset($rules['max_length']) && !empty($rules['max_length']['fields'])) {
            foreach ($rules['max_length']['fields'] as $field => $maxLength) {
                $value = $profile->getData($field);
                if ($value !== null && $value > $maxLength) {
                    $inputException->addError(
                        $this->getErrorMessage(
                            InputException::ERROR_TYPE_MAX_LENGTH,
                            $field,
                            ['value' => $value, 'maxLength' => $maxLength]
                        )
                    );
                }
            }
        }
    }

    /**
     * Validate data type
     *
     * @param string $dataType
     * @param array $fields
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileUpdateInterface $profile
     * @param \Riki\SubscriptionMachine\Exception\InputException $inputException
     * @throws LocalizedException
     * @throws \Zend_Validate_Exception
     */
    private function _validateDataType($dataType, $fields, $profile, $inputException)
    {
        foreach ($fields as $field) {
            $value = $profile->getData($field);
            if (!\Zend_Validate::is($value, $dataType)) {
                $values = ['value' => $profile->getData($field)];
                $inputException->addError($this->getErrorMessage(
                    InputException::ERROR_TYPE_INVALID_VALUE,
                    $field,
                    $values
                ));
            }
        }
    }

    /**
     * Get customer by consumer db id
     *
     * @param string $consumerDbId
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomerByConsumerDbId($consumerDbId)
    {
        $filter = $this->searchCriteriaBuilder
            ->addFilter('consumer_db_id', $consumerDbId, 'eq')
            ->setPageSize(1)
            ->create();
        try {
            $customers = $this->customerRepository->getList($filter);
            foreach ($customers->getItems() as $customer) {
                return $customer;
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Get course
     * @param string $courseCode
     * @return bool|null|\Riki\SubscriptionCourse\Api\Data\SubscriptionCourseInterface
     */
    public function getSubscriptionCourse($courseCode)
    {
        try {
            $course = $this->subscriptionCourseRepository->getCourseByCode($courseCode);
            if ($course && $course->getIsEnable()) {
                return $course;
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * @param string $errorType
     * @param string $field
     * @param null $value
     * @return Phrase
     * @throws LocalizedException
     */
    public function getErrorMessage($errorType, $field, $value = null)
    {
        $arguments['fieldName'] = $field;
        if (!empty($value)) {
            $arguments = array_merge($arguments, $value);
        }

        switch ($errorType) {
            case InputException::ERROR_TYPE_REQUIRED:
                $phrase = __('"%fieldName" is required. Enter and try again.', $arguments);
                break;
            case InputException::ERROR_TYPE_INVALID_VALUE:
                $phrase = __('Invalid value of "%value" provided for the %fieldName field.', $arguments);
                break;
            case InputException::ERROR_TYPE_MUST_EXIST:
                $phrase = __('No such entity with %fieldName = %fieldValue', $arguments);
                break;
            case InputException::ERROR_TYPE_MIN_VALUE:
                $phrase = __(
                    'The %fieldName value of "%value" must be greater than or equal to %minValue.',
                    $arguments
                );
                break;
            case InputException::ERROR_TYPE_MAX_VALUE:
                $phrase = __(
                    'The %fieldName value of "%value" must be less than or equal to %maxValue.',
                    $arguments
                );
                break;
            case InputException::ERROR_TYPE_MAX_LENGTH:
                $phrase = __(
                    'The %fieldName length of "%value" must be less than or equal to %maxLength.',
                    $arguments
                );
                break;
            default:
                throw new LocalizedException(__('Invalid %errorType', ['errorType' => $errorType]));
        }

        return $phrase;
    }

    /**
     * Get reference profile if it pass and has data
     * @param int $profileId
     * @return bool|\Riki\Subscription\Api\Data\ApiProfileInterface
     */
    public function getProfileById($profileId)
    {
        if (!empty($profileId)) {
            try {
                $profile = $this->profileRepository->get($profileId);
                if ($profile->getStatus()) {
                    return $profile;
                }
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Validate frequency
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileCreationInterface $monthlyFeeProfile
     * @return bool
     * @throws InputException
     * @throws LocalizedException
     */
    public function validateFrequency($monthlyFeeProfile)
    {
        $course = $this->getSubscriptionCourse($monthlyFeeProfile->getSubscriptionCourseCode());
        if ($course) {
            $frequencyInterval = $monthlyFeeProfile->getFrequencyInterval();
            $frequencyUnit = $monthlyFeeProfile->getFrequencyUnit();
            $monthlyFeeProfileFrequency = $frequencyInterval . ' ' . $frequencyUnit;
            $frequencies = $this->courseFactory->create()->getFrequencyEntities($course->getId());
            if (!empty($frequencies)) {
                foreach ($frequencies as $frequency) {
                    $subscriptionCourseFrequency
                        = $frequency['frequency_interval'] . ' ' . $frequency['frequency_unit'];
                    if (strtolower($monthlyFeeProfileFrequency) == strtolower($subscriptionCourseFrequency)) {
                        return true;
                    }
                }
            }
        }

        // phpcs:ignore MEQP2.Classes.ObjectInstantiation
        $inputException = new InputException();
        $inputException->setErrorCode(2003);
        $inputException->addError(
            __(
                'No such entity with %fieldName = %fieldValue, %field2Name = %field2Value',
                [
                    'fieldName' => 'frequency_interval',
                    'fieldValue' => $monthlyFeeProfile->getFrequencyInterval(),
                    'field2Name' => 'frequency_unit',
                    'field2Value' => $monthlyFeeProfile->getFrequencyUnit()
                ]
            )
        );

        throw $inputException;
    }

    /**
     * Check profile is monthly fee
     *
     * @param int $profileId
     * @return boolean
     */
    public function isMonthlyFeeProfile($profileId)
    {
        try {
            $profile = $this->profileRepository->get($profileId);
            if ($profile->getStatus()) {
                $course = $this->subscriptionCourseRepository->get($profile->getCourseId());
                if ($course->getIsEnable() && $course->getSubscriptionType() == CourseType::TYPE_MONTHLY_FEE) {
                    return true;
                }
            }
        } catch (NoSuchEntityException $e) {
            // do nothing
            return false;
        }

        return false;
    }

    /**
     * Check subscription course for monthly fee
     * @param string $courseCode
     * @return bool|null|\Riki\SubscriptionCourse\Api\Data\SubscriptionCourseInterface
     * @throws InputException
     */
    public function isMonthlyFeeSubscriptionCourse($courseCode)
    {
        try {
            $course = $this->subscriptionCourseRepository->getCourseByCode($courseCode);
            if ($course && $course->getIsEnable() && $course->getSubscriptionType() == CourseType::TYPE_MONTHLY_FEE) {
                return $course;
            }
        } catch (\Exception $e) {
            // do nothing
        }

        // phpcs:ignore MEQP2.Classes.ObjectInstantiation
        $inputException = new InputException();
        $inputException->setErrorCode(2004);
        $inputException->addError(
            __(
                'The %fieldName value of "%value" must be a type of "%type"',
                [
                    'fieldName' => 'subscription_course_code',
                    'value' => $courseCode,
                    'type' => CourseType::TYPE_MONTHLY_FEE
                ]
            )
        );
        throw $inputException;
    }
}
