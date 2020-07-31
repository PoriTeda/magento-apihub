<?php

namespace Riki\Subscription\Controller\Adminhtml\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Riki\Subscription\Helper\Order\Data as HelperSubOrder;
use Riki\Subscription\Helper\Profile\Data as SubscriptionHelperProfile;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;

class Create extends Action
{
    /**
     * @var HelperSubOrder
     */
    protected $helperSubOrder;
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;
    /**
     * @var
     */
    protected $messageManager;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    protected $profileFactory;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $profileHelperData;

    /* @var \Magento\Framework\Stdlib\DateTime\DateTime */
    protected $_datetime;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_sessionManager;

    /**
     * @var \Riki\Subscription\Logger\LoggerOrder
     */
    protected $logger;

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
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subOrderHelper;

    protected $subscriptionValidator;

    protected $profileCache;

    /**
     * @var \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator
     */
    protected $monthlyFeeProfileValidator;

    /**
     * Create constructor.
     * @param \Riki\Subscription\Model\Email\ProfilePaymentMethodErrorBusiness $profilePaymentMethodErrorBusinessEmail
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\Subscription\Model\Email\ProfilePaymentMethodError $profilePaymentMethodErrorEmail
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param SubscriptionHelperProfile $subscriptionHelperProfile
     * @param Context $context
     * @param HelperSubOrder $helperSubOrder
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Riki\Subscription\Logger\LoggerOrder $logger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $profileIndexer
     * @param \Riki\Subscription\Helper\Order $subOrderHelper
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCache
     * @param \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $monthlyFeeProfileValidator
     */
    public function __construct(
        \Riki\Subscription\Model\Email\ProfilePaymentMethodErrorBusiness $profilePaymentMethodErrorBusinessEmail,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\Subscription\Model\Email\ProfilePaymentMethodError $profilePaymentMethodErrorEmail,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        SubscriptionHelperProfile $subscriptionHelperProfile,
        Context $context,
        HelperSubOrder $helperSubOrder,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Riki\Subscription\Logger\LoggerOrder $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $profileIndexer,
        \Riki\Subscription\Helper\Order $subOrderHelper,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCache,
        \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $monthlyFeeProfileValidator
    )
    {
        $this->profileIndexer = $profileIndexer;
        $this->profilePaymentMethodErrorBusinessEmail = $profilePaymentMethodErrorBusinessEmail;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->profileRepository = $profileRepository;
        $this->profilePaymentMethodErrorEmail = $profilePaymentMethodErrorEmail;
        $this->_datetime = $dateTime;
        $this->profileHelperData = $subscriptionHelperProfile;
        $this->helperSubOrder = $helperSubOrder;
        $this->messageManager = $context->getMessageManager();
        $this->layoutFactory = $layoutFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->profileFactory = $profileFactory;
        $this->_sessionManager = $sessionManager;
        $this->logger = $logger;
        $this->timezone = $timezone;
        $this->subOrderHelper = $subOrderHelper;
        $this->subscriptionValidator = $subscriptionValidator;
        $this->profileCache = $profileCache;
        $this->monthlyFeeProfileValidator = $monthlyFeeProfileValidator;
        parent::__construct($context);
    }

    public function execute()
    {
        $arrPost = $this->getRequest()->getParams();
        $profile_id = isset($arrPost['profile_id']) ? $arrPost['profile_id'] : null;
        // 2.3.2 - editable to new paygent
        $isNewPaygent = isset($arrPost['new_paygent']) ? $arrPost['new_paygent'] == 1 : null;
        if ($profile_id) {
            $this->profileIndexer->removeCacheInvalid($profile_id);
            $this->profileCache->removeCache($profile_id);
            /** @var \Riki\Subscription\Model\Profile\Profile $profileModel */
            $profileModel = $this->profileFactory->create()->load($profile_id);
            if ($profileModel->getId() and $profileModel->getData('create_order_flag') != 1) {
                // If profile is monthly fee and is_monthly_fee_confirmed is 0
                // This profile is not allowed generate order
                if ($this->monthlyFeeProfileValidator->isMonthlyFeeProfile($profile_id) &&
                    !$profileModel->getData('is_monthly_fee_confirmed')
                ) {
                    $this->messageManager->addError(
                        __('This profile #' . $profile_id . ' can\'t generate order due to it is not confirmed')
                    );
                    $this->logger->addError(
                        __('This profile #' . $profile_id . ' can\'t generate order due to it is not confirmed')
                    );
                } else {
                    $profileData = $this->helperSubOrder->getProfile($profile_id);
                    if ($profileData) {
                        if ($this->profileHelperData->isTmpProfileId($profile_id)
                            and $profileModel->isWaitingToDisengaged()) {
                            $profile_id = $this->profileHelperData->getProfileOriginFromTmp($profile_id);
                            $profileData['profile_id'] = $profile_id;
                            $profileData['order_times'] = $profileData['order_times'] - 1;
                        }
                        $result = null;
                        $this->helperSubOrder->getRegistry()->register(
                            HelperSubOrder::PROFILE_GENERATE_STATE_REGISTRY_NAME,
                            true
                        );

                        try {
                            $this->profilePaymentMethodErrorEmail
                                ->getVariables()
                                ->setData('profile', $profileModel);
                            $this->logger->info(
                                'Start generates order from subscription profile #' . $profile_id
                            );
                            $result = $this->helperSubOrder->createMageOrder(
                                $profileData,
                                $arrPost,
                                false,
                                $isNewPaygent
                            );
                        } catch (\Bluecom\Paygent\Exception\PaygentAuthorizedException $e) {
                            $paymentErrorCode = (string)current($e->getParameters());
                            $profileModel->setData('paymentErrorCode', $paymentErrorCode);
                            $this->onPaygentAuthorizeFailed($profileModel);
                            $this->logger->info($e->getMessage());
                            $this->messageManager->addError($e->getMessage());
                        } catch (LocalizedException $e) {
                            $this->messageManager->addError($e->getMessage());
                        } catch (\Exception $e) {
                            $this->logger->critical($e);
                            $this->messageManager->addError(
                                __('Cannot create order from subscription profile ' . $profile_id)
                            );
                        }

                        try {
                            if (!$result) {
                                $originDate = $this->timezone->formatDateTime($this->_datetime->gmtDate(), 2);
                                $today = $this->_datetime->gmtDate('Y-m-d', $originDate);
                                if (strtotime($profileModel->getData('next_order_date')) <= strtotime($today)) {
                                    $arrDataForTempProfile[1]['delivery_date'] = '';
                                    if ($this->profileHelperData->calculateDeliveryDateForTmp($profile_id) !== false) {
                                        $deliveryDateTmpObj = $this->profileHelperData->calculateDeliveryDateForTmp(
                                            $profile_id
                                        );
                                        $arrDataForTempProfile[1]['delivery_date'] = $deliveryDateTmpObj->format(
                                            'Y/m/d'
                                        );
                                    }
                                    $this->profileHelperData->CheckAndMakeTmpProfile(
                                        $profileModel,
                                        $arrDataForTempProfile
                                    );
                                }
                            } elseif (isset($result) && $result->getId()) {
                                try {
                                    // Get product cart of sequence Hanpukai before update for validate maximum qty
                                    $courseData = $this->subOrderHelper->loadCourse($profileModel->getCourseId());
                                    if (isset($courseData['subscription_type']) &&
                                        isset($courseData['hanpukai_type']) &&
                                        $courseData['subscription_type'] == CourseType::TYPE_HANPUKAI &&
                                        $courseData['hanpukai_type'] == CourseType::TYPE_HANPUKAI_SEQUENCE
                                    ) {
                                        $listSequenceHanpukaiProduct = $profileModel->getProductCart();
                                    }

                                    $this->profileHelperData->rollBack($profile_id);
                                    $this->helperSubOrder->resetCouponCode($profileModel);
                                } catch (\Exception $e) {
                                    $this->logger->addError($e->getMessage());
                                }

                                if (!empty($result->getCouponCode())) {
                                    $this->logger->info('Coupon code: ' . $result->getCouponCode());
                                }

                                if (!empty($result->getAppliedRuleIds())) {
                                    $this->logger->info('Applied Rule Ids: ' . $result->getAppliedRuleIds());
                                }

                                $subscriptionCourse = $this->subOrderHelper->loadCourse($profileModel->getCourseId());
                                $validateResults = $this->subOrderHelper->validateAmountRestriction(
                                    $result,
                                    $subscriptionCourse,
                                    $profileModel
                                );
                                if (!$validateResults['status']) {
                                    $this->logger->info('Subscription ' . $profile_id .
                                        ' has total amount below the threshold [' .
                                        $validateResults['min'] .
                                        ' JPY]');
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
                                    $this->logger->info($messageError);
                                }
                                $this->logger->info('Subscription ' .
                                    $profile_id .
                                    ' has created one order No.' .
                                    $result->getIncrementId());

                                // redirect to order view
                                if ($isNewPaygent && $result) {
                                    return $this->_redirect('sales/order/view', ['order_id' => $result->getId()]);
                                }
                            }
                        } catch (LocalizedException $e) {
                            $this->messageManager->addError($e->getMessage());
                        } catch (\Exception $e) {
                            $this->logger->critical($e);
                            $this->messageManager->addError(__('An error occurred while processing the profile.'));
                        }
                    } else {
                        $this->messageManager->addError(
                            __('Cannot create order from subscription profile ' . $profile_id)
                        );
                        $this->logger->addError(__('Cannot create order from subscription profile ' . $profile_id));
                    }
                }
            } else {
                $this->messageManager->addError(__('Profile not found'));
            }
        } else {
            $this->messageManager->addError('Profile not found');
        }

        $this->_redirect('profile/profile/edit', ['id' => $profile_id]);
    }

    /**
     * On paygent authorize failed
     *
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     *
     * @return void
     */
    protected function onPaygentAuthorizeFailed(\Riki\Subscription\Model\Profile\Profile $profile)
    {
        $authorizationFailedTime = (int)$profile->getData('authorization_failed_time');
        $profile->setData('authorization_failed_time', ($authorizationFailedTime + 1));
        $profile->setData('last_authorization_failed_date', $this->_datetime->gmtDate());
        if (is_null($profile->getData('type'))
            && $profile->getData('payment_method') == \Bluecom\Paygent\Model\Paygent::CODE
        ) {
            $profile->setData('payment_method', new \Zend_Db_Expr('NULL'));
        }
        $profile->save(); // should use repository

        if ($profile->getData('type') == 'version') {
            $mainProfile = $this->profileFactory->create()->load($profile->getId(), null, true);
            if ($mainProfile->getId()
                && $mainProfile->getData('payment_method') == \Bluecom\Paygent\Model\Paygent::CODE
            ) {
                $mainProfile->setData('payment_method', new \Zend_Db_Expr('NULL'));
                $mainProfile->save();
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
            $versionProfile->setPaymentMethod(new \Zend_Db_Expr('NULL'))->save();
        }

        $tmpCriteria = $this->searchCriteriaBuilder
            ->addFilter('tmp_parent_profile_id', $profile->getProfileId())
            ->addFilter('main_table.payment_method', \Bluecom\Paygent\Model\Paygent::CODE)
            ->create();
        $tmpProfiles = $this->profileRepository
            ->getList($tmpCriteria)
            ->getItems();
        /** @var \Riki\Subscription\Model\Profile\Profile $tmpProfile */
        foreach ($tmpProfiles as $tmpProfile) {
            $tmpProfile->setPaymentMethod(new \Zend_Db_Expr('NULL'))->save();
        }

        $this->profilePaymentMethodErrorEmail->send();
        $this->profilePaymentMethodErrorBusinessEmail
            ->addItem(['profile' => $profile])
            ->send();
    }
}
