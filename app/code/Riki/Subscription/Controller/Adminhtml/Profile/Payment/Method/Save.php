<?php
namespace Riki\Subscription\Controller\Adminhtml\Profile\Payment\Method;

use Magento\Framework\Exception\LocalizedException;
use Riki\Subscription\Helper\Order\Data as HelperSubOrder;
use Riki\Subscription\Helper\Profile\Data as SubscriptionHelperProfile;
class Save extends \Riki\Subscription\Controller\Adminhtml\Profile\AbstractProfile
{
    const ADMIN_RESOURCE = 'Riki_Subscription::edit_payment_method';

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SubscriptionHelperProfile
     */
    protected $profileHelperData;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_datetime;

    /**
     * @var \Riki\Subscription\Logger\LoggerOrder
     */
    protected $orderLogger;

    /**
     * @var \Riki\Subscription\Model\Email\ProfilePaymentMethodError
     */
    protected $profilePaymentMethodErrorEmail;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var HelperSubOrder
     */
    protected $helperSubOrder;

    /**
     * @var \Riki\Subscription\Logger\LoggerDeleteProfile
     */
    protected $loggerDeleteProfile;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subOrderHelper;

    /**
     * Save constructor.
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\Subscription\Model\Email\ProfilePaymentMethodError $profilePaymentMethodErrorEmail
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param SubscriptionHelperProfile $subscriptionHelperProfile
     * @param HelperSubOrder $helperSubOrder
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Subscription\Logger\LoggerOrder $orderLogger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Riki\Subscription\Logger\LoggerDeleteProfile $loggerDeleteProfile
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Riki\Subscription\Helper\Order $subOrderHelper
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\Subscription\Model\Email\ProfilePaymentMethodError $profilePaymentMethodErrorEmail,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        SubscriptionHelperProfile $subscriptionHelperProfile,
        HelperSubOrder $helperSubOrder,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Logger\LoggerOrder $orderLogger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\Subscription\Logger\LoggerDeleteProfile $loggerDeleteProfile,
        \Magento\Backend\App\Action\Context $context,
        \Riki\Subscription\Helper\Order $subOrderHelper
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->profilePaymentMethodErrorEmail = $profilePaymentMethodErrorEmail;
        $this->_datetime = $dateTime;
        $this->profileHelperData = $subscriptionHelperProfile;
        $this->helperSubOrder = $helperSubOrder;
        $this->messageManager = $context->getMessageManager();
        $this->profileFactory = $profileFactory;
        $this->orderLogger = $orderLogger;
        $this->timezone = $timezone;
        $this->profileRepository = $profileRepository;
        $this->loggerDeleteProfile = $loggerDeleteProfile;
        $this->subOrderHelper = $subOrderHelper;
        parent::__construct($profileFactory, $searchCriteriaBuilder,$logger,$profileRepository, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\Controller\ResultInterface|bool
     */
    public function execute()
    {
        $postValues = $this->getRequest()->getPostValue();
        $id = isset($postValues['id'])
            ? $postValues['id']
            : $this->getRequest()->getParam('id', 0);

        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->profileFactory->create()->load($id);
        if (!$profile->getId()) {
            $this->_forward('no-route');
            return false;
        }

        $result = $this->resultRedirectFactory
            ->create()
            ->setPath('customer/index/edit', [
                'id' => $profile->getCustomerId(),
                'active_tab' => 'customer_edit_profile_tab'
            ]);
        if (!isset($postValues['payment_method'])
            || !$postValues['payment_method']
        ) {
            $this->messageManager->addError(__('Invalid data'));
            return $result;
        }
        if (!isset($postValues['delivery_date_new'])
            || !$postValues['delivery_date_new']
        ) {
            $this->messageManager->addError(__('Invalid data'));
            return $result;
        }
        if (!isset($postValues['delivery_timeslot_new'])
            || !$postValues['delivery_timeslot_new']
        ) {
            $this->messageManager->addError(__('Invalid data'));
            return $result;
        }


        if ($profile->getPaymentMethod()) {
            return $result;
        }

        try {
            if (is_null($profile->getData('type'))) {
                try {
                    $this->profileHelperData->changePaymentAndDeliveryDateForProfile($profile, $postValues);
                }catch (\Exception $e) {
                    $this->logger->critical($e);
                    $this->messageManager->addError('An error occurred while saving profile');
                    return $result;
                }
            } else {
                /** @var \Riki\Subscription\Model\Profile\Profile $mainProfile */
                $mainProfile = $this->profileFactory->create()->load($profile->getId(), null, true);
                try {
                    $this->profileHelperData->changePaymentAndDeliveryDateForProfile($mainProfile, $postValues);
                }catch (\Exception $e) {
                    $this->logger->critical($e);
                    $this->messageManager->addError('An error occurred while saving profile');
                    return $result;
                }
            }

            $versionCriteria = $this->searchCriteriaBuilder
                ->addFilter('version_parent_profile_id', $profile->getProfileId())
                ->addFilter('subscription_profile_version.status', 1)
                ->addFilter('main_table.payment_method', new \Zend_Db_Expr('NULL'), 'is')
                ->create();
            $versionProfiles = $this->profileRepository
                ->getList($versionCriteria)
                ->getItems();
            /** @var \Riki\Subscription\Api\Data\ApiProfileInterface $versionProfile */
            foreach ($versionProfiles as $versionProfile) {
                $newOrderDate = $this->profileHelperData->calculatorNextOrderDateFromProfile($postValues['delivery_date_new'],$versionProfile->getProfileId());
                $this->profileRepository->save($versionProfile->setPaymentMethod($postValues['payment_method'])->setNextDeliveryDate($postValues['delivery_date_new'])->setNextOrderDate($newOrderDate)->getDataModel());
                $this->updateProductCart($versionProfile->getProfileId(),$postValues);
            }

            $tmpCriteria = $this->searchCriteriaBuilder
                ->addFilter('tmp_parent_profile_id', $profile->getProfileId())
                ->create();
            $tmpProfiles =  $this->profileRepository
                ->getList($tmpCriteria)
                ->getItems();
            /** @var \Riki\Subscription\Api\Data\ApiProfileInterface $tmpProfile */
            foreach ($tmpProfiles as $tmpProfile) {
                $newDeliveryDate = $this->profileHelperData->calculateDeliveryDateForTmp($profile->getProfileId())->format('Y-m-d');
                $newOrderDate = $this->profileHelperData->calculatorNextOrderDateFromProfile($newDeliveryDate,$tmpProfile->getProfileId());
                if($this->checkDeleteProfileTemp($newDeliveryDate,$newOrderDate))
                {
                    //delete profile temp
                    $arrDataLog = [
                        'main_profile_id'  =>$profile->getProfileId(),
                        'data_profile_temp'=>$tmpProfile->getData(),
                        'new_delivery_date' =>$newDeliveryDate,
                        'new_order_date'   => $newOrderDate
                    ];
                    $this->deleteProfileTmpAndLog($tmpProfile,$arrDataLog);
                }else{
                    $this->profileRepository->save($tmpProfile->setPaymentMethod($postValues['payment_method'])->setNextDeliveryDate($newDeliveryDate)->setNextOrderDate($newOrderDate)->getDataModel());
                    $this->updateProductCart($tmpProfile->getProfileId(),['delivery_date_new'=>$newDeliveryDate,'delivery_timeslot_new'=>$postValues['delivery_timeslot_new']]);
                }
            }

            $this->messageManager->addSuccess(__('Update profile successfully!'));
            $this->profileHelperData->resetProfileSession($id);
            if(isset($postValues['ivr']) and $postValues['ivr'] =='ivr_now' ){
                $order = $this->generateOrderWithIVR($id);
                if($order) {
                    $this->_redirect('sales/order/view', ['order_id' => $order->getId()]);
                    return;
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->info($e);
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addError('An error occurred while saving payment method into profile');
        }

        return $result;
    }
    public function generateOrderWithIVR($profileId){
        $isNewPaygent = true;
        if ($profileId) {
            /** @var \Riki\Subscription\Model\Profile\Profile $profileModel */
            $profileModel = $this->profileFactory->create()->load($profileId);
            if ($profileModel->getId() and $profileModel->getData('create_order_flag') != 1) {
                $profileData = $this->helperSubOrder->getProfile($profileId);

                $result = null;

                $this->helperSubOrder->getRegistry()->register(HelperSubOrder::PROFILE_GENERATE_STATE_REGISTRY_NAME, true);

                try {
                    $this->profilePaymentMethodErrorEmail
                        ->getVariables()
                        ->setData('profile', $profileModel);
                    $this->orderLogger->info("Start generates order from subscription profile #" . $profileId);
                    $result = $this->helperSubOrder->createMageOrder($profileData, null, false, $isNewPaygent);
                } catch (\Bluecom\Paygent\Exception\PaygentAuthorizedException $e) {
                    $paymentErrorCode = (string)current($e->getParameters());
                    $profileModel->setData('paymentErrorCode', $paymentErrorCode);
                    $this->onPaygentAuthorizeFailed($profileModel);
                    $this->orderLogger->info($e->getMessage());
                    $this->messageManager->addError($e->getMessage());
                } catch (LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\Exception $e) {
                    $this->orderLogger->critical($e);
                    $this->messageManager->addError(__('Cannot create order for profile.'));
                }

                try {
                    if (!$result) {
                        $originDate = $this->timezone->formatDateTime($this->_datetime->gmtDate(), 2);
                        $today = $this->_datetime->gmtDate('Y-m-d', $originDate);
                        if (strtotime($profileModel->getData('next_order_date')) <= strtotime($today)) {
                            $arrDataForTempProfile[1]['delivery_date'] = '';
                            if ($this->profileHelperData->calculateDeliveryDateForTmp($profileId) !== false) {
                                $deliveryDateTmpObj = $this->profileHelperData->calculateDeliveryDateForTmp($profileId);
                                $arrDataForTempProfile[1]['delivery_date'] = $deliveryDateTmpObj->format('Y/m/d');
                            }
                            $this->profileHelperData->CheckAndMakeTmpProfile($profileModel, $arrDataForTempProfile);
                        }
                    } elseif (isset($result) && $result->getId()) {
                        $this->updateProfileWhenTmp($profileId,$result);
                        try {
                            $this->profileHelperData->rollBack($profileId);
                        } catch (\Exception $e) {
                            $this->orderLogger->addError($e->getMessage());
                        }

                        $subscriptionCourse = $this->subOrderHelper->loadCourse($profileModel->getCourseId());
                        $validateResults = $this->subOrderHelper->validateAmountRestriction(
                            $result,
                            $subscriptionCourse,
                            $profileModel
                        );
                        if (!$validateResults['status']) {
                            $this->orderLogger->info('Subscription '.$profileId.' has total amount below the threshold ['.$validateResults['min'].' JPY]');
                        }
                        $this->orderLogger->info("Subscription " . $profileId . " has created one order No." . $result->getIncrementId());
                        // redirect to order view
                        if ($isNewPaygent && $result) {
                            return $result;
                        }
                    }
                } catch (LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\Exception $e) {
                    $this->orderLogger->critical($e);
                    $this->messageManager->addError(__('An error occurred while processing the profile.'));
                }
            } else {
                $this->messageManager->addError(__('Subscription profile created a order for next delivery'));
            }
        } else {
            $this->messageManager->addError('Profile not found');
        }

        return false;
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
        $profile->setData('authorization_failed_time',($authorizationFailedTime+1));
        $profile->setData('last_authorization_failed_date',$this->_datetime->gmtDate());
        $profile->setData('payment_method', new \Zend_Db_Expr('NULL'));
        $profile->save(); // should use repository

        $versionCriteria = $this->searchCriteriaBuilder
            ->addFilter('version_parent_profile_id', $profile->getProfileId())
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
        $tmpProfiles =  $this->profileRepository
            ->getList($tmpCriteria)
            ->getItems();
        /** @var \Riki\Subscription\Model\Profile\Profile $tmpProfile */
        foreach ($tmpProfiles as $tmpProfile) {
            $tmpProfile->setPaymentMethod(new \Zend_Db_Expr('NULL'))->save();
        }

        $this->profilePaymentMethodErrorEmail->send();
    }

    /**
     * Update trading_id for tmp profile if it used IVR now
     *
     * @param $profileId
     * @param \Magento\Sales\Model\Order $order
     */
    protected function updateProfileWhenTmp($profileId,$order){
        $profileTmp = $this->profileHelperData->getTmpProfile($profileId);
        if($profileTmp) {
            $profileTmpModel = $this->profileFactory->create()->load($profileTmp->getData('linked_profile_id'));
            $profileTmpModel->setData('trading_id', $order->getIncrementId());
            try {
                $profileTmpModel->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    protected function updateProductCart($profileId,array $dataChange) {
        $profileModel = $this->profileFactory->create()->load($profileId);
        $profileProductCart = $profileModel->getProductCart();
        foreach ($profileProductCart as $productCart) {
            $productCart->setData('delivery_date',$dataChange['delivery_date_new']);
            $productCart->setData('delivery_time_slot',$dataChange['delivery_timeslot_new']);
            try{
                $productCart->save();
            }catch (\Exception $e) {
                throw $e;
            }
        }
    }

    /**
     * Check delete profile temp
     *
     * @param $newDeliveryDate
     * @param $nextOrderDate
     * @return bool
     */
    public function checkDeleteProfileTemp($newDeliveryDate,$nextOrderDate)
    {
        $originDate = $this->timezone->formatDateTime($this->_datetime->gmtDate(), 2);
        $today = $this->_datetime->gmtDate('Y-m-d', $originDate);

        if(strtotime($newDeliveryDate)>$today && strtotime($nextOrderDate)>$today)
        {
            return true;
        }

        return false;
    }

    /**
     * Log data of profile when delete profile temp
     *
     * @param $tmpProfile
     * @param $arrContent
     */
    public function deleteProfileTmpAndLog($tmpProfile,$arrContent)
    {
        $profileId = $tmpProfile->getProfileId();

        $isDelete = $this->profileRepository->deleteById($profileId);
        if($isDelete)
        {
            $salesConnection= $this->profileFactory->create()->getResource()->getConnection('sales');
            $sql = "DELETE FROM subscription_profile_link WHERE linked_profile_id = $profileId";
            $salesConnection->query($sql);
        }
        $this->loggerDeleteProfile->infoProfileTempDeleted($tmpProfile,$arrContent);
    }

}