<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Model\Profile\Order;

use Magento\Framework\Exception\LocalizedException;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;

class Generate
{
    /**
     * @var \Riki\Subscription\Logger\LoggerOrder
     */
    protected $_logger;
    /**
     * @var \Riki\Subscription\Logger\LoggerPublishMessageQueue
     */
    protected $_loggerQueue;
    /**
     * @var \Riki\Subscription\Helper\Order\Data
     */
    protected $_orderData;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $_profileFactory;
    /**
     * @var \Riki\Subscription\Model\Version\VersionFactory
     */
    protected $_profileVersion;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_datetime;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_helperProfileData;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var \Riki\Subscription\Model\Email\ProfilePaymentMethodError
     */
    protected $profilePaymentMethodErrorEmail;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Riki\Subscription\Model\Email\ProfilePaymentMethodErrorBusiness
     */
    protected $profilePaymentMethodErrorBusinessEmail;

    protected $profileIndexer;
    /**
     * @var \Riki\Subscription\Logger\LoggerFreeMachine
     */
    protected $loggerFreeMachine;
    /**
     * @var \Riki\Subscription\Helper\StockPoint\Data
     */
    protected $stockPointHelper;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subOrderHelper;

    protected $subscriptionValidator;

    /**
     * @var \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator
     */
    protected $monthlyFeeProfileValidator;

    /**
     * Generate constructor.
     * @param \Riki\Subscription\Model\Email\ProfilePaymentMethodErrorBusiness $profilePaymentMethodErrorBusinessEmail
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\Subscription\Model\Email\ProfilePaymentMethodError $profilePaymentMethodErrorEmail
     * @param \Riki\Subscription\Logger\LoggerOrder $logger
     * @param \Riki\Subscription\Logger\HandlerOrder $handlerCSV
     * @param \Riki\Subscription\Logger\LoggerPublishMessageQueue $loggerPublishMessageQueue
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Subscription\Model\Version\VersionFactory $versionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile\Proxy $profileIndexer
     * @param \Riki\Subscription\Helper\Order $subOrderHelper
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     * @param \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $monthlyFeeProfileValidator
     */
    public function __construct(
        \Riki\Subscription\Model\Email\ProfilePaymentMethodErrorBusiness $profilePaymentMethodErrorBusinessEmail,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\Subscription\Model\Email\ProfilePaymentMethodError $profilePaymentMethodErrorEmail,
        \Riki\Subscription\Logger\LoggerOrder $logger,
        \Riki\Subscription\Logger\HandlerOrder $handlerCSV,
        \Riki\Subscription\Logger\LoggerPublishMessageQueue $loggerPublishMessageQueue,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Model\Version\VersionFactory $versionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile\Proxy $profileIndexer,
        \Riki\Subscription\Helper\Order $subOrderHelper,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $monthlyFeeProfileValidator
    ) {
        $this->profileIndexer = $profileIndexer;
        $this->profilePaymentMethodErrorBusinessEmail = $profilePaymentMethodErrorBusinessEmail;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->profileRepository = $profileRepository;
        $this->profilePaymentMethodErrorEmail = $profilePaymentMethodErrorEmail;
        $this->_logger = $logger;
        $this->handlerCSV = $handlerCSV;
        $this->_loggerQueue = $loggerPublishMessageQueue;
        $this->_profileFactory = $profileFactory;
        $this->_profileVersion = $versionFactory;
        $this->_datetime = $datetime;
        $this->objectManager = $objectManager;
        $this->stockPointHelper = $stockPointHelper;
        $this->timezone = $timezone;
        $this->subOrderHelper = $subOrderHelper;
        $this->subscriptionValidator = $subscriptionValidator;
        $this->monthlyFeeProfileValidator = $monthlyFeeProfileValidator;
    }

    /**
     * @param \Riki\Subscription\Api\GenerateOrder\ProfileBuilderInterface $message
     * @param int $num
     * @param string $consumerName
     * @return bool
     * @throws \Exception
     */
    public function createOrderFromQueue(\Riki\Subscription\Api\GenerateOrder\ProfileBuilderInterface $message, $num = 0, $consumerName = '')
    {
        $this->handlerCSV->setDynamicFileLog($consumerName);
        $this->_logger->setHandlers(['system' => $this->handlerCSV]);

        $profileId = null;
        $this->_orderData = $this->objectManager->create('\Riki\Subscription\Helper\Order\Data');
        $this->_helperProfileData = $this->objectManager->create('\Riki\Subscription\Helper\Profile\Data');
        foreach ($message->getItems() as $profileObject) {
            $profileId = $profileObject->getProfileId();
        }
        if ($num > 1) {
            throw new \LogicException('Try to generate order for profile #'.$profileId.' still failed');
        }
        try {
            $this->_logger->info("Start generates order from subscription profile #" . $profileId);
            $this->updateProfile($profileId);
            $this->profileIndexer->removeCacheInvalid($profileId);
            $profileModel = $this->_profileFactory->create()->load($profileId);
            // If profile is monthly fee and is_monthly_fee_confirmed is 0
            // This profile is not allowed generate order
            if ($this->monthlyFeeProfileValidator->isMonthlyFeeProfile($profileId) &&
                !$profileModel->getData('is_monthly_fee_confirmed')
            ) {
                $this->_logger->addError(
                    __('This profile #' . $profileId . ' can\'t generate order due to it is not confirmed')
                );
            } else {
                $profileData = $this->_orderData->getProfile($profileId);
                if ($profileData) {
                    $order = null;

                    try {
                        if (isset($profileData['hanpukai_error_msg'])) {
                            $this->_logger->info($profileData['hanpukai_error_msg']);
                            $this->_logger->info('Cannot create order from subscription profile ' . $profileId);
                            $this->_loggerQueue->info($profileData['hanpukai_error_msg']);
                            return true;
                        }
                        if ($profileModel->getStatus() != \Riki\Subscription\Model\Profile\Profile::STATUS_DISABLED) {
                            $this->profilePaymentMethodErrorEmail->getVariables()
                                ->setData('profile', $profileModel);
                            $order = $this->_orderData->createMageOrder($profileData);
                        } else {
                            $this->_logger->info('The profile # ' . $profileId . ' has been disengaged.');
                            $this->_logger->info('Cannot create order from subscription profile ' . $profileId);
                            $this->_loggerQueue->info('The profile # ' . $profileId . ' has been disengaged.');
                            return true;
                        }
                    } catch (\Bluecom\Paygent\Exception\PaygentAuthorizedException $e) {
                        $paymentErrorCode = (string)current($e->getParameters());
                        $profileModel->setData('paymentErrorCode', $paymentErrorCode);
                        $this->onPaygentAuthorizeFailed($profileModel);
                        $this->_logger->info($e);
                    } catch (LocalizedException $e) {
                        if ($this->_orderData->checkDeadlock($e)) {
                            throw $e;
                        } else {
                            $this->_logger->info($e);
                        }
                    } catch (\Exception $e) {
                        if ($this->_orderData->checkDeadlock($e)) {
                            throw $e;
                        } else {
                            $this->_logger->critical($e);
                        }
                    }

                    if (!$order) {
                        $this->_logger->info('Cannot create order from subscription profile ' . $profileId);
                        $this->_loggerQueue->info(
                            'The message of profile # ' . $profileId . ' failed to run on queue.'
                        );
                        // Make tmp if order not create
                        $originDate = $this->timezone->formatDateTime($this->_datetime->gmtDate(), 2);
                        $today = $this->_datetime->gmtDate('Y-m-d', $originDate);
                        if (strtotime($profileModel->getData('next_order_date')) <= strtotime($today)) {
                            $arrDataForTempProfile[1]['delivery_date'] = '';
                            if ($this->_helperProfileData->calculateDeliveryDateForTmp($profileId) !== false) {
                                $deliveryDateTmpObj = $this->_helperProfileData->calculateDeliveryDateForTmp(
                                    $profileId
                                );
                                $arrDataForTempProfile[1]['delivery_date'] = $deliveryDateTmpObj->format('Y/m/d');
                            }
                            $this->_helperProfileData->CheckAndMakeTmpProfile($profileModel, $arrDataForTempProfile);
                        }
                    } elseif (isset($order) && $order->getId()) {
                        try {
                            // Get product cart of sequence Hanpukai before update for validate maximum qty
                            $courseData = $this->subOrderHelper->loadCourse($profileModel->getCourseId());
                            if (isset($courseData['subscription_type']) && isset($courseData['hanpukai_type']) &&
                                $courseData['subscription_type'] == CourseType::TYPE_HANPUKAI &&
                                $courseData['hanpukai_type'] == CourseType::TYPE_HANPUKAI_SEQUENCE
                            ) {
                                $listSequenceHanpukaiProduct = $profileModel->getProductCart();
                            }

                            $this->_helperProfileData->rollBack($profileId);
                            $this->_orderData->resetCouponCode($profileModel);
                        } catch (\Exception $e) {
                            $this->_logger->addError($e->getMessage());
                        }

                        if (!empty($order->getCouponCode())) {
                            $this->_logger->info('Coupon code: ' . $order->getCouponCode());
                        }

                        if (!empty($order->getAppliedRuleIds())) {
                            $this->_logger->info('Applied Rule Ids: ' . $order->getAppliedRuleIds());
                        }

                        $subscriptionCourse = $this->subOrderHelper->loadCourse($profileModel->getCourseId());
                        $validateResults = $this->subOrderHelper->validateAmountRestriction(
                            $order,
                            $subscriptionCourse,
                            $profileModel
                        );
                        if (!$validateResults['status']) {
                            $this->_logger->info(
                                'Subscription ' . $profileId . ' has total amount below the threshold [' .
                                $validateResults['min'] . ' JPY]'
                            );
                        }

                        /** Validate maximum qty restriction */
                        $listProduct = isset($listSequenceHanpukaiProduct)
                            ? $listSequenceHanpukaiProduct
                            : $profileModel->getProductCart();
                        $validateMaximumQty = $this->subscriptionValidator
                            ->setProfileId($profileModel->getProfileId())
                            ->setIsGenerate(true)
                            ->setProductCarts($listProduct)
                            ->validateMaximumQtyRestriction();
                        if ($validateMaximumQty['error']) {
                            $productErrors = $validateMaximumQty['product_errors'];
                            $maxQty = $validateMaximumQty['maxQty'];
                            $orderTimes = $profileModel->getData('order_times') + 1;
                            $messageError = $this->subscriptionValidator->getMessageErrorValidateMaxQtyForGenerate(
                                $productErrors,
                                $maxQty,
                                $orderTimes
                            );
                            $this->_logger->info($messageError);
                        }
                        $this->_logger->info(
                            'Subscription ' . $profileId . ' has created one order No.' . $order->getIncrementId()
                        );
                        $this->_loggerQueue->info(
                            'The message of profile # ' . $profileId . ' created a order No.' . $order->getIncrementId()
                        );
                    }
                } else {
                    $this->_logger->addError(__('Cannot create order from subscription profile ' . $profileId));
                }
            }
        } catch (\CredisException $e) {
            // can re-try
            $this->_logger->critical($e);
            $this->createOrderFromQueue($message, ++$num, $consumerName);
        } catch (LocalizedException $e) {
            $this->_logger->info($e);
        } catch (\Exception $e) {
            if ($this->_orderData->checkDeadlock($e)) {
                $this->_logger->info(
                    'Subscription ' . $profileId .
                    ' has been waiting long time for processing so it will be pushed to queue again'
                );
                $this->_logger->critical($e);
            } else {
                $this->_logger->critical($e);
            }
            throw $e;
        }
    }

    /**
     * Reset value of column "publish_message" of profile failed to generate order
     * if: profile has version then reset value on version profile
     * else: reset on main profile
     *
     * @param $profileId
     */
    public function updateProfile($profileId)
    {
        $version = $this->_helperProfileData->checkProfileHaveVersion($profileId);
        if ($version) {
            $profileModel = $this->_profileFactory->create()->load($version);
        } else {
            $profileModel = $this->_profileFactory->create()->load($profileId);
        }
        if ($profileModel->getId()) {
            $profileModel->setData('publish_message', 0);
            if ($profileModel->getData('skip_next_delivery') == 1) {
                /*dont use skip_next_delivery column but have to temporary disable skip_next_delivery*/
                $profileModel->setSkipNextDelivery(0);
            }
            try {
                $profileModel->save();
            } catch (\Exception $e) {
                $this->_loggerQueue->critical($e);
            }
        }
    }

    /**
     * On paygent authorize failed
     *
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     *
     * @return void
     */
    public function onPaygentAuthorizeFailed(\Riki\Subscription\Model\Profile\Profile $profile)
    {
        $isStockPointProfile = $profile->isStockPointProfile();
        $authorizationFailedTime = (int)$profile->getData('authorization_failed_time');
        $profile->setData('authorization_failed_time', ($authorizationFailedTime+1));
        $profile->setData('last_authorization_failed_date', $this->_datetime->gmtDate());
        if (is_null($profile->getData('type'))
            && $profile->getData('payment_method') == \Bluecom\Paygent\Model\Paygent::CODE
        ) {
            $profile->setData('payment_method', new \Zend_Db_Expr('NULL'));
        }

        /*clear stock point data for case re authorized failed*/
        $profile->setData('stock_point_profile_bucket_id', new \Zend_Db_Expr('NULL'));
        $profile->setData('stock_point_delivery_type', new \Zend_Db_Expr('NULL'));
        $profile->setData('stock_point_delivery_information', new \Zend_Db_Expr('NULL'));

        try {
            $profile->save();
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }

        $mainProfileId = $profile->getId();

        if ($profile->getData('type') == 'version') {
            $mainProfile = $this->_profileFactory->create()->load($profile->getId(), null, true);
            if ($mainProfile->getId()) {
                $mainProfileId = $mainProfile->getId();
                if ($mainProfile->getData('payment_method') == \Bluecom\Paygent\Model\Paygent::CODE) {
                    $mainProfile->setData('payment_method', new \Zend_Db_Expr('NULL'));

                    /*clear stock point data for case re authorized failed*/
                    $mainProfile->setData('stock_point_profile_bucket_id', new \Zend_Db_Expr('NULL'));
                    $mainProfile->setData('stock_point_delivery_type', new \Zend_Db_Expr('NULL'));
                    $mainProfile->setData('stock_point_delivery_information', new \Zend_Db_Expr('NULL'));

                    try {
                        $mainProfile->save();
                    } catch (\Exception $e) {
                        $this->_logger->info($e->getMessage());
                    }
                }
            }
        }

        $versionCriteria = $this->searchCriteriaBuilder
            ->addFilter('version_parent_profile_id', $profile->getProfileId())
            ->addFilter('subscription_profile_version.status', 1)
            ->addFilter('main_table.payment_method', \Bluecom\Paygent\Model\Paygent::CODE)
            ->create();
        $versionProfiles = $this->profileRepository
            ->getList($versionCriteria)
            ->getItems();
        /** @var \Riki\Subscription\Model\Profile\Profile $versionProfile */
        foreach ($versionProfiles as $versionProfile) {
            $versionProfile->setPaymentMethod(new \Zend_Db_Expr('NULL'));

            /*clear stock point data for case re authorized failed*/
            $versionProfile->setData('stock_point_profile_bucket_id', new \Zend_Db_Expr('NULL'));
            $versionProfile->setData('stock_point_delivery_type', new \Zend_Db_Expr('NULL'));
            $versionProfile->setData('stock_point_delivery_information', new \Zend_Db_Expr('NULL'));

            try {
                $versionProfile->save();
            } catch (\Exception $e) {
                $this->_logger->info($e->getMessage());
            }
        }

        $tmpCriteria = $this->searchCriteriaBuilder
            ->addFilter('tmp_parent_profile_id', $profile->getProfileId())
            ->addFilter('main_table.payment_method', \Bluecom\Paygent\Model\Paygent::CODE)
            ->create();
        $tmpProfiles =  $this->profileRepository
            ->getList($tmpCriteria)
            ->getItems();
        /** @var \Riki\Subscription\Model\Profile\Profile $tmpProfile */
        foreach ($tmpProfiles as $tmpProfile) {
            $tmpProfile->setPaymentMethod(new \Zend_Db_Expr('NULL'));

            /*clear stock point data for case re authorized failed*/
            $tmpProfile->setData('stock_point_profile_bucket_id', new \Zend_Db_Expr('NULL'));
            $tmpProfile->setData('stock_point_delivery_type', new \Zend_Db_Expr('NULL'));
            $tmpProfile->setData('stock_point_delivery_information', new \Zend_Db_Expr('NULL'));

            try {
                $tmpProfile->save();
            } catch (\Exception $e) {
                $this->_logger->info($e->getMessage());
            }
        }

        $this->profilePaymentMethodErrorEmail->send();
        $this->profilePaymentMethodErrorBusinessEmail
            ->getVariables()
            ->setData('batchMode', 1);
        $this->profilePaymentMethodErrorBusinessEmail
            ->addItem(['profile' => $profile]);

        /*call api to remove stock point*/
        if ($isStockPointProfile) {
            try {
                $this->_logger->info('Call Api to remove bucket for profile '. $mainProfileId);
                $response = $this->stockPointHelper->removeFromBucket($mainProfileId);

                if ($response && !empty($response['success'])) {
                    $this->_logger->info('Remove bucket for profile '. $mainProfileId. ' success.');
                } else {
                    $this->_logger->info('Remove bucket for profile '. $mainProfileId. ' failed.');

                    if (!empty($response) && isset($response['message'])) {
                        $this->_logger->info($response['message']);
                    }
                }
            } catch (\Exception $e) {
                $this->_logger->info($e->getMessage());
            }
        }
    }
}
