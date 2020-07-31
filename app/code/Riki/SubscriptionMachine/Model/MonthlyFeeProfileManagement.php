<?php

namespace Riki\SubscriptionMachine\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\PaymentBip\Model\InvoicedBasedPayment;
use Riki\SubscriptionMachine\Api\MonthlyFeeProfileManagementInterface;
use Riki\SubscriptionMachine\Exception\InputException;
use Magento\Framework\Exception\PaymentException;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;
use Bluecom\Paygent\Model\Paygent;

class MonthlyFeeProfileManagement implements MonthlyFeeProfileManagementInterface
{
    const AUTHORIZATION_AMOUNT = 1;

    /**
     * @var \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileResultInterfaceFactory
     */
    protected $monthlyFeeProfileResultFactory;

    /**
     * @var MonthlyFeeProfile\Validator
     */
    protected $validator;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $profileRepository;

    /**
     * @var \Riki\Subscription\Api\Data\ApiProfileInterfaceFactory
     */
    protected $profileFactory;

    /**
     * @var \Riki\Subscription\Model\ProductCart\ProfileProductCartRepository
     */
    protected $profileProductCartRepository;

    /**
     * @var \Riki\Subscription\Api\Data\ApiProductCartFactory
     */
    protected $productCartFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
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
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Riki\Framework\Helper\Transaction\Database
     */
    protected $dbTransaction;

    /**
     * @var \Riki\SubscriptionMachine\Logger\ApiLogger
     */
    protected $apiLogger;

    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course
     */
    protected $subCourseResourceModel;

    /**
     * @var \Bluecom\Paygent\Model\PaygentManagement
     */
    protected $paygentManagement;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * MonthlyFeeProfileManagement constructor.
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileResultInterfaceFactory $monthlyFeeProfileResultFactory
     * @param MonthlyFeeProfile\Validator $validator
     * @param \Riki\Subscription\Model\ProductCart\ProfileProductCartRepository $profileProductCartRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\Subscription\Api\Data\ApiProfileInterfaceFactory $profileFactory
     * @param \Riki\Subscription\Api\Data\ApiProductCartInterfaceFactory $productCartFactory
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $subscriptionCourseRepository
     * @param \Riki\Framework\Helper\Transaction\Database $dbTransaction
     * @param \Riki\SubscriptionMachine\Logger\ApiLogger $apiLogger
     * @param \Bluecom\Paygent\Model\PaygentManagement $paygentManagement
     */
    public function __construct(
        \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileResultInterfaceFactory $monthlyFeeProfileResultFactory,
        \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $validator,
        \Riki\Subscription\Model\ProductCart\ProfileProductCartRepository $profileProductCartRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\Subscription\Api\Data\ApiProfileInterfaceFactory $profileFactory,
        \Riki\Subscription\Api\Data\ApiProductCartInterfaceFactory $productCartFactory,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $subscriptionCourseRepository,
        \Riki\Framework\Helper\Transaction\Database $dbTransaction,
        \Riki\SubscriptionMachine\Logger\ApiLogger $apiLogger,
        \Bluecom\Paygent\Model\PaygentManagement $paygentManagement,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->monthlyFeeProfileResultFactory = $monthlyFeeProfileResultFactory;
        $this->validator = $validator;
        $this->helperProfile = $helperProfile;
        $this->customerRepository = $customerRepository;
        $this->profileRepository = $profileRepository;
        $this->profileFactory = $profileFactory;
        $this->profileProductCartRepository = $profileProductCartRepository;
        $this->productCartFactory = $productCartFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->storeManager = $storeManager;
        $this->subscriptionCourseRepository = $subscriptionCourseRepository;
        $this->dbTransaction = $dbTransaction;
        $this->apiLogger = $apiLogger;
        $this->paygentManagement = $paygentManagement;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function create($monthlyFeeProfile)
    {
        /**
         * Validate data
         */
        $this->validator->validateCreationRules($monthlyFeeProfile);

        /**
         * @var \Riki\Subscription\Api\Data\ApiProfileInterface $referenceProfile
         */
        $referenceProfile = $this->_resolveReferenceProfile(
            $monthlyFeeProfile->getConsumerdbCustomerId(),
            $monthlyFeeProfile->getSubscriptionCourseCode(),
            $monthlyFeeProfile->getReferenceProfileId()
        );

        /**
         * Validate frequency
         */
        $this->validator->validateFrequency($monthlyFeeProfile);

        /**
         * Validate subscription course monthly fee
         */
        $this->validator->isMonthlyFeeSubscriptionCourse($monthlyFeeProfile->getSubscriptionCourseCode());

        $this->dbTransaction->beginTransaction();

        try {
            //Process data if it does not has error
            $referenceProfileId = $referenceProfile->getProfileId();
            list($billingAddressId, $shippingAddressId) = $this->_getAddressFromProductCart($referenceProfileId);

            //Calculate next order date
            $monthlyFeeProfile->setNextOrderDate(
                $this->_calculateNextOrderDate($monthlyFeeProfile, $referenceProfileId)
            );

            $profileData = $this->_buildProfileData($monthlyFeeProfile, $referenceProfile);

            /** @var \Riki\Subscription\Api\Data\ApiProfileInterface $newProfile */
            $newProfile = $this->profileRepository->save($profileData);

            //save product cart
            $this->_saveProductCart($monthlyFeeProfile, $newProfile, $shippingAddressId, $billingAddressId);

            //verify card
            //$this->_verifyCreditCard($newProfile, $referenceProfile->getTradingId());

            $this->dbTransaction->commit();
        } catch (PaymentException $e) {
            $this->dbTransaction->rollback();

            // phpcs:ignore MEQP2.Classes.ObjectInstantiation
            $inputException = new InputException();
            $inputException->setErrorCode(2007);
            $inputException->addError(
                __(
                    'Profile cannot created successfully due to issue from Paygent: %errorMessage',
                    [
                        'errorMessage' => $e->getMessage()
                    ]
                )
            );
            throw $inputException;
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();
            $this->apiLogger->critical($e, ['monthlyFeeCreate' => $monthlyFeeProfile->getData()]);

            // phpcs:ignore MEQP2.Classes.ObjectInstantiation
            $inputException = new InputException();
            $inputException->setErrorCode(2006);
            $inputException->addError(__('There is some thing wrong in the system.'));
            throw $inputException;
        }

        /**
         * @var \Riki\SubscriptionMachine\Model\Data\MonthlyFeeProfileResult $result
         */
        $result = $this->monthlyFeeProfileResultFactory->create();
        $result->setReferenceProfileId($referenceProfileId);
        $result->setCreatedProfileId($newProfile->getProfileId());
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function update($monthlyFeeProfile)
    {
        /**
         * Validate data
         */
        $this->validator->validateUpdateRules($monthlyFeeProfile);

        /**
         * Check profile exist is monthly free
         */
        $profileId = $monthlyFeeProfile->getProfileId();
        $profile = $this->_isMonthlyFeeProfile($monthlyFeeProfile->getProfileId());

        try {
            /**
             * Calculate next order date
             */
            $monthlyFeeProfile->setNextOrderDate($this->_calculateNextOrderDate($monthlyFeeProfile, $profileId));

            /**
             * Only update false => true once.
             */
            $isConfirmed = $profile->getIsMonthlyFeeConfirmed() ? true : $monthlyFeeProfile->getIsMonthlyFeeConfirmed();

            /** @var \Riki\Subscription\Api\Data\ApiProfileInterface $dataProfile */
            $dataProfile = $this->profileFactory->create();
            $dataProfile->setNextDeliveryDate($monthlyFeeProfile->getNextDeliveryDate());
            $dataProfile->setNextOrderDate($monthlyFeeProfile->getNextOrderDate());
            $dataProfile->setProfileId($profileId);
            $dataProfile->setVariableFee($monthlyFeeProfile->getVariableFee());
            $dataProfile->setIsMonthlyFeeConfirmed($isConfirmed);
            $dataProfile->setMonthlyFeeLabel($monthlyFeeProfile->getMonthlyFeeLabel());
            $dataProfile->setDataGenerateDeliveryDate(
                $this->isPossibilityExcessInNextMonth($monthlyFeeProfile->getNextDeliveryDate())
            );

            $this->dbTransaction->beginTransaction();

            //Update profile data
            $this->profileRepository->save($dataProfile);

            try {
                $this->_updateProductCart($monthlyFeeProfile, $profile);
                $this->dbTransaction->commit();
                return true;
            } catch (\Exception $e) {
                $this->dbTransaction->rollback();
                $this->apiLogger->critical($e, ['monthlyFeeUpdate' => $monthlyFeeProfile->getData()]);

                // phpcs:ignore MEQP2.Classes.ObjectInstantiation
                $inputException = new InputException();
                $inputException->setErrorCode(2006);
                $inputException->addError(__('There is some thing wrong in the system.'));
                throw $inputException;
            }
        } catch (\Exception $e) {
            $this->apiLogger->critical($e, ['monthlyFeeUpdate' => $monthlyFeeProfile->getData()]);

            // phpcs:ignore MEQP2.Classes.ObjectInstantiation
            $inputException = new InputException();
            $inputException->setErrorCode(2006);
            $inputException->addError(__('There is some thing wrong in the system.'));
            throw $inputException;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disengage($disengagementProfile)
    {
        /**
         * Validate data
         */
        $this->validator->validateDisengageRules($disengagementProfile);

        /**
         * Check profile exist is monthly free
         */
        $profileId = $disengagementProfile->getProfileId();
        $this->_isMonthlyFeeProfile($profileId);

        try {
            $reasons = implode(',', $disengagementProfile->getDisengagementReasons());
            /** @var \Riki\Subscription\Api\Data\ApiProfileInterface $dataProfile */
            $dataProfile = $this->profileFactory->create();
            $dataProfile->setDisengagementDate($disengagementProfile->getDisengagementDate());
            $dataProfile->setDisengagementUser($disengagementProfile->getDisengagementUser());
            $dataProfile->setDisengagementReason($reasons);
            $dataProfile->setStatus(false);
            $dataProfile->setProfileId($profileId);

            //Update profile data
            $this->profileRepository->save($dataProfile);
            return true;
        } catch (\Exception $e) {
            $this->apiLogger->critical($e, ['disengagementProfile' => $disengagementProfile->getData()]);

            // phpcs:ignore MEQP2.Classes.ObjectInstantiation
            $inputException = new InputException();
            $inputException->setErrorCode(2006);
            $inputException->addError(__('There is some thing wrong in the system.'));
            throw $inputException;
        }
    }

    /**
     * @param mixed $monthlyFeeProfile
     * @param int $profileId
     * @return string|null
     */
    private function _calculateNextOrderDate($monthlyFeeProfile, $profileId)
    {
        $nextDeliveryDate = $monthlyFeeProfile->getNextDeliveryDate();
        $nextOrderDate = $monthlyFeeProfile->getNextOrderDate();
        if (empty($nextOrderDate)) {
            $nextOrderDate = $this->helperProfile->calculatorNextOrderDateFromProfile($nextDeliveryDate, $profileId);
            $nextOrderDate = date('Y-m-d', strtotime($nextOrderDate));
        }
        return $nextOrderDate;
    }

    /**
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileCreationInterface $monthlyFeeProfileCreation
     * @param \Riki\Subscription\Api\Data\ApiProfileInterface $referenceProfile
     * @return \Riki\Subscription\Api\Data\ApiProfileInterface
     */
    private function _buildProfileData($monthlyFeeProfileCreation, $referenceProfile)
    {
        /**
         * Get current store call api
         */
        $storeId = $storeId = $this->storeManager->getStore()->getId();

        /**
         * @var \Riki\SubscriptionCourse\Api\Data\SubscriptionCourseInterface $course
         */
        $course = $this->subscriptionCourseRepository->getCourseByCode(
            $monthlyFeeProfileCreation->getSubscriptionCourseCode()
        );

        /**
         * @var \Riki\Subscription\Api\Data\ApiProfileInterface $dataProfile
         */
        $dataProfile = $this->profileFactory->create();
        $dataProfile->setCourseId($course->getId());
        $dataProfile->setCourseName($course->getCourseName());
        $dataProfile->setCustomerId($referenceProfile->getCustomerId());
        $dataProfile->setStoreId($storeId);
        $dataProfile->setFrequencyUnit($monthlyFeeProfileCreation->getFrequencyUnit());
        $dataProfile->setFrequencyInterval($monthlyFeeProfileCreation->getFrequencyInterval());
        $dataProfile->setPaymentMethod($referenceProfile->getPaymentMethod());
        $dataProfile->setShippingCondition('riki_shipping_riki_shipping');
        $dataProfile->setNextDeliveryDate($monthlyFeeProfileCreation->getNextDeliveryDate());
        $dataProfile->setNextOrderDate($monthlyFeeProfileCreation->getNextOrderDate());
        $dataProfile->setStatus(1);
        $dataProfile->setOrderTimes(1);
        $dataProfile->setVariableFee($monthlyFeeProfileCreation->getVariableFee());
        $dataProfile->setReferenceProfileId($referenceProfile->getProfileId());
        $dataProfile->setIsMonthlyFeeConfirmed(false);
        $dataProfile->setMonthlyFeeLabel($monthlyFeeProfileCreation->getMonthlyFeeLabel());
        $dataProfile->setDataGenerateDeliveryDate(
            $this->isPossibilityExcessInNextMonth($monthlyFeeProfileCreation->getNextDeliveryDate())
        );
        return $dataProfile;
    }

    /**
     * @param mixed $monthlyFeeProfile
     * @param \Riki\Subscription\Api\Data\ApiProfileInterface $profile
     * @param int $shippingAddressId
     * @param int $billingAddressId
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function _saveProductCart($monthlyFeeProfile, $profile, $shippingAddressId, $billingAddressId)
    {
        $products = $monthlyFeeProfile->getProducts();
        foreach ($products as $item) {
            $product = $this->productRepository->get($item->getSku());

            /** @var \Riki\Subscription\Api\Data\ApiProductCartInterface $productCart */
            $productCart = $this->productCartFactory->create();
            $productCart->setProfileId($profile->getProfileId());
            $productCart->setQty($item['qty']);
            $productCart->setProductType($product->getTypeId());
            $productCart->setProductId($product->getId());
            $productCart->setBillingAddressId($billingAddressId);
            $productCart->setShippingAddressId($shippingAddressId);
            $productCart->setDeliveryDate($monthlyFeeProfile->getNextDeliveryDate());
            $this->profileProductCartRepository->save($productCart);
        }
    }

    /**
     * @param mixed $monthlyFeeProfile
     * @param \Riki\Subscription\Api\Data\ApiProfileInterface $profile
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function _updateProductCart($monthlyFeeProfile, $profile)
    {
        $filter = $this->searchCriteriaBuilder
            ->addFilter('profile_id', $profile->getProfileId(), 'eq')
            ->create();
        $result = $this->profileProductCartRepository->getList($filter);

        $listProductNeedUpdated = [];
        foreach ($monthlyFeeProfile->getProducts() as $item) {
            $listProductNeedUpdated[$item->getSku()] = $item;
        }

        $shippingAddressId = $billingAddressId = 0;
        if ($result && $result->getTotalCount() > 0) {
            foreach ($result->getItems() as $item) {
                $shippingAddressId = $item->getShippingAddressId();
                $billingAddressId = $item->getBillingAddressId();

                /**
                 * Update next delivery date for old product.
                 */
                try {
                    $product = $this->productRepository->getById($item->getProductID());
                    if (!isset($listProductNeedUpdated[$product->getSku()])) {
                        /** @var \Riki\Subscription\Api\Data\ApiProductCartInterface $productCart */
                        $productCart = $this->productCartFactory->create();
                        $productCart->setDeliveryDate($monthlyFeeProfile->getNextDeliveryDate());
                        $item->save($productCart);
                    } else {
                        //delete old product
                        $item->delete();
                    }
                } catch (NoSuchEntityException $e) {
                    $this->apiLogger->critical($e);
                }
            }
        }

        //Save new product cart
        $this->_saveProductCart($monthlyFeeProfile, $profile, $shippingAddressId, $billingAddressId);
    }

    /**
     * @param int $profileId
     * @return array
     */
    private function _getAddressFromProductCart($profileId)
    {
        $result = [null, null];
        $productCart = $this->profileRepository->getListProductCart($profileId);
        foreach ($productCart->getItems() as $product) {
            return [
                $product->getData('billing_address_id'),
                $product->getData('shipping_address_id')
            ];
        }
        return $result;
    }

    /**
     * @param string $consumerDbId
     * @param string $courseCode
     * @param int $referenceProfileId
     * @return \Riki\Subscription\Api\Data\ApiProfileInterface
     * @throws InputException
     */
    private function _resolveReferenceProfile($consumerDbId, $courseCode, $referenceProfileId = null)
    {
        /** @var \Riki\SubscriptionCourse\Model\Course $course */
        $course = $this->subscriptionCourseRepository->getCourseByCode($courseCode);
        $customer = $this->_getCustomerByConsumerDbId($consumerDbId);
        $referenceProfile = false;

        //IF ref_profile_id is NOT empty, the system will get profile information by using "ref_profile_id
        if ($referenceProfileId !== null) {
            try {
                $referenceProfile = $this->profileRepository->get($referenceProfileId);
                if ($referenceProfile->getCustomerId() != $customer->getId()
                || (!in_array($referenceProfile->getPaymentMethod(), $course->getAllowPaymentMethod()) && $referenceProfile->getPaymentMethod() != null)
                || ($referenceProfile->getPaymentMethod() == null && $referenceProfile->getAuthorizationFailedTime() == 0)
                ) {
                    $referenceProfile = false;
                } elseif ($referenceProfile->getPaymentMethod() == null && $referenceProfile->getAuthorizationFailedTime() > 0) {
                    $referenceProfile->setPaymentMethod(Paygent::CODE);
                }
            } catch (\Exception $e) {
                $referenceProfile = false;
            }
        } else {
            // IF the system will get Max(profile_id) from subscription profile meet these conditions
            $sortOrder = $this->sortOrderBuilder->setField('profile_id')
                ->setDirection(\Magento\Framework\Api\SortOrder::SORT_DESC)
                ->create();

            $filters = [
                $this->filterBuilder->setField('payment_method')->setConditionType('in')->setValue($course->getAllowPaymentMethod())->create(),
                $this->filterBuilder->setField('payment_method')->setConditionType('null')->create()
            ];

            $filterGroup[] = $this->filterGroupBuilder->setFilters($filters)->create();
            $filterGroup[] = $this->filterGroupBuilder->addFilter($this->filterBuilder->setField('type')->setConditionType('null')->setValue(true)->create())->create();
            $filterGroup[] = $this->filterGroupBuilder->addFilter($this->filterBuilder->setField('customer_id')->setConditionType('eq')->setValue($customer->getId())->create())->create();
            $filterGroup[] = $this->filterGroupBuilder->addFilter($this->filterBuilder->setField('status')->setConditionType('eq')->setValue(1)->create())->create();

            $filter = $this->searchCriteriaBuilder
                ->setFilterGroups($filterGroup)
                ->setSortOrders([$sortOrder])
                ->setPageSize(20)
                ->create();
            $profileList = $this->profileRepository->getList($filter);
            if ($profileList && $profileList->getTotalCount() > 0) {
                foreach ($profileList->getItems() as $item) {
                    if($item->getPaymentMethod() == null && $item->getData('authorization_failed_time') == 0) {
                        $this->apiLogger->info(print_r($item->getData(), true));
                        continue;
                    } else {
                        $item->setPaymentMethod(Paygent::CODE);
                    }

                    $referenceProfile = $item;
                    break;
                }
            }
        }

        if (!$referenceProfile) {
            $this->apiLogger->info('Can not find a reference profile to create monthly profile.');
            // phpcs:ignore MEQP2.Classes.ObjectInstantiation
            $inputException = new InputException(__('The system cannot create profile due to missing of information.'));
            $inputException->setErrorCode(2005);
            throw $inputException;
        }

        return $referenceProfile;
    }

    /**
     * Check profile monthly fee
     * @param int $profileId
     * @return \Riki\Subscription\Api\Data\ApiProfileInterface
     * @throws InputException
     */
    private function _isMonthlyFeeProfile($profileId)
    {
        try {
            $profile = $this->profileRepository->get($profileId);
            if ($profile->getStatus()) {
                $course = $this->subscriptionCourseRepository->get($profile->getCourseId());
                if ($course->getIsEnable() && $course->getSubscriptionType() == CourseType::TYPE_MONTHLY_FEE) {
                    return $profile;
                }
            }
        } catch (NoSuchEntityException $e) {
            // do nothing
        }

        // phpcs:ignore MEQP2.Classes.ObjectInstantiation
        $inputException = new InputException();
        $inputException->setErrorCode(2004);
        $inputException->addError(
            __(
                'The %fieldName value of "%value" must be a type of "%type"',
                ['fieldName' => 'profile_id', 'value' => $profileId, 'type' => 'monthly_fee']
            )
        );
        throw $inputException;
    }

    /**
     * @param string $nextDeliveryDate
     * @return bool|string
     */
    private function isPossibilityExcessInNextMonth($nextDeliveryDate)
    {
        $dayOfMonth = (int)date('d', strtotime($nextDeliveryDate));
        if ($dayOfMonth > 28) {
            return $dayOfMonth;
        }
        return false;
    }

    /**
     * @param string $consumerDbId
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     * @throws InputException
     */
    private function _getCustomerByConsumerDbId($consumerDbId)
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
            //do not thing
        }

        // phpcs:ignore MEQP2.Classes.ObjectInstantiation
        $inputException = new InputException();
        $inputException->setErrorCode(2003);
        $inputException->addError(
            __(
                'No such entity with %fieldName = %fieldValue',
                ['fieldName' => 'consumerdb_customer_id', 'value' => $consumerDbId]
            )
        );
        throw $inputException;
    }

    /**
     * @param \Riki\Subscription\Api\Data\ApiProfileInterface $profile
     * @param string $referenceTradingId
     * @throws LocalizedException
     * @throws PaymentException
     */
    private function _verifyCreditCard($profile, $referenceTradingId)
    {
        if ($profile->getPaymentMethod() != Paygent::CODE) {
            throw new LocalizedException(__('This payment method is not allowed to authorize.'));
        }

        $history = [
            'customer_id' => $profile->getCustomerId(),
            'profile_id' => $profile->getProfileId(),
            'trading_id' => $referenceTradingId
        ];

        list(
            $status,
            $result,
            $paymentObject
        ) = $this->paygentManagement->executeAuthorize(
            self::AUTHORIZATION_AMOUNT,
            $profile->getProfileId(),
            $referenceTradingId,
            $history
        );

        if (!$status) {
            $this->apiLogger->addInfo('AUTHORIZATION FAILED', ['result' => json_encode($result)]);
            $errorMessage = $this->paygentManagement->getPaygentModel()
                ->getErrorMessageByErrorCode($paymentObject->getResponseDetail());
            throw new PaymentException(
                __($errorMessage)
            );
        } else {
            /**
             * Save trading id after authorize
             */
            $profile->setTradingId($profile->getProfileId());
            $this->profileRepository->save($profile);

            /**
             * Void 1 yen after authorize
             */
            list (
                $status,
                $result
                ) = $this->paygentManagement->executeVoid($result['payment_id'], $profile->getProfileId(), $history);
            if (!$status) {
                //log error
                $this->apiLogger->addInfo('VOID FAILED', ['result' => json_encode($result)]);
            }
        }
    }
}
