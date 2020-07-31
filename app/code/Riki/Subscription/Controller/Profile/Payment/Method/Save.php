<?php
namespace Riki\Subscription\Controller\Profile\Payment\Method;

class Save extends \Riki\Subscription\Controller\Profile
{
    /**
     * @var \Bluecom\Paygent\Api\PaygentManagementInterface
     */
    protected $paygentManagement;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Url
     */
    protected $urlBuilder;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    /**
     * @var \Riki\Subscription\Logger\LoggerDeleteProfile
     */
    protected $loggerDeleteProfile;

    /**
     * @var \Riki\Subscription\Logger\LoggerStateProfile
     */
    protected $loggerStateProfile;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_datetime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper
     */
    protected $deliveryDateGenerateHelper;

    /**
     * Save constructor.
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Magento\Framework\Url $urlBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Bluecom\Paygent\Api\PaygentManagementInterface $paygentManagement
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Framework\Registry $registry
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Riki\Subscription\Logger\LoggerDeleteProfile $loggerDeleteProfile
     * @param \Riki\Subscription\Logger\LoggerStateProfile $loggerStateProfile
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Framework\Url $urlBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Bluecom\Paygent\Api\PaygentManagementInterface $paygentManagement,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Action\Context $context,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\Subscription\Logger\LoggerDeleteProfile $loggerDeleteProfile,
        \Riki\Subscription\Logger\LoggerStateProfile $loggerStateProfile,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->paygentManagement = $paygentManagement;
        $this->helperProfile = $helperProfile;
        $this->loggerDeleteProfile = $loggerDeleteProfile;
        $this->loggerStateProfile = $loggerStateProfile;
        $this->_datetime = $dateTime;
        $this->timezone = $timezone;
        $this->deliveryDateGenerateHelper = $deliveryDateGenerateHelper;
        parent::__construct($profileFactory, $profileRepository, $customerSession, $customerUrl, $registry, $logger, $context, $helperProfile);
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

        // profile only support set profile main via factory
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->profileFactory->create()->load($id);
        if (!$profile->getId()) {
            $this->_forward('no-route');
            return false;
        }
        if ($this->_profileData->isTmpProfileId($id, $profile)) {
            $this->_forward('no-route');
            return false;
        }
        if(is_array($postValues['delivery_date_new'])){
            foreach ($postValues['delivery_date_new'] as $address =>$groupByDeliveryType) {
                $postValues['delivery_date_new'] = reset($groupByDeliveryType);
            }
        }
        if(is_array($postValues['delivery_timeslot_new'])){
            foreach ($postValues['delivery_timeslot_new'] as $address =>$groupByDeliveryType) {
                $postValues['delivery_timeslot_new'] = reset($groupByDeliveryType);
            }
        }

        $result = $this->resultRedirectFactory
            ->create()
            ->setPath('subscriptions/profile');
        if (!isset($postValues['payment_method'])
            || !$postValues['payment_method']
        ) {
            $this->messageManager->addError(__('Invalid data'));
            return $result;
        }

        if ($profile->getPaymentMethod()) {
            return $result;
        }

        if ($profile->getCustomerId() != $this->customerSession->getCustomerId()) {
            return $result;
        }

        if ($postValues['payment_method'] == \Bluecom\Paygent\Model\Paygent::CODE) {
            $paygentParams = [
                'trading_id' => $profile->getId() . strtotime(date('Y-m-d H:i:s')),
                'amount' => 1,
                'return_url' => $this->urlBuilder->getUrl('subscriptions/profile', [
                    '_scope' => $profile->getStoreId(),
                    '_nosid' => true,
                ]),
                'inform_url' => $this->urlBuilder->getUrl('subscriptions/paygent/response/payment_method_update/1', [
                    'id' => $profile->getProfileId(),
                    '_scope' => $profile->getStoreId(),
                    '_nosid' => true
                ])
            ];
            $paygentLink = $this->paygentManagement->getRedirectAuthorizeLink($paygentParams);
            if (isset($paygentLink['url']) && $paygentLink['url']) {
                $result->setUrl($paygentLink['url']);
                $mainProfile = $this->profileFactory->create()->load($profile->getId(), null, true);
                if ($mainProfile->getId()) {
                    $this->updateProfileBeforeAuthorize($mainProfile,$paygentParams['trading_id'],$postValues);
                }
                //save trading for version profile
                $versionId = $this->helperProfile->checkProfileHaveVersion($profile->getId());
                if ($versionId) {
                    $versionProfile = $this->profileFactory->create()->load($versionId);
                    if ($versionProfile->getId()) {
                        $this->updateProfileBeforeAuthorize($versionProfile,$paygentParams['trading_id'],$postValues);
                    }
                }
                //save trading for profile temp
                $tempProfileLink = $this->helperProfile->getTmpProfile($profile->getId());
                if ($tempProfileLink) {
                    $tempProfileId = $tempProfileLink->getLinkedProfileId();
                    $tempProfile = $this->profileFactory->create()->load($tempProfileId);
                    if ($tempProfile->getId()) {
                        $this->updateProfileBeforeAuthorize($tempProfile,$paygentParams['trading_id'],$postValues,$profile->getId());
                    }
                }
                $this->messageManager->addSuccess(__('Update profile successfully!'));
                $this->_profileData->resetProfileSession($id);
            } else {
                $this->messageManager->addError(__('An error occurred while authorizing paygent.'));
            }

            return $result;
        }

        try {
            if (is_null($profile->getData('type'))) {
                try {
                    $this->helperProfile->changePaymentAndDeliveryDateForProfile($profile, $postValues);

                    $this->loggerStateProfile->info(sprintf(
                        'Profile has been changed from edit payment page, ID:%s, post data: %s',
                        $profile->getId(),
                        json_encode($postValues)
                    ));
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                    $this->messageManager->addError('An error occurred while saving profile');
                    return $result;
                }
            } else {
                /** @var \Riki\Subscription\Model\Profile\Profile $mainProfile */
                $mainProfile = $this->profileFactory->create()->load($profile->getId(), null, true);
                try {
                    $this->helperProfile->changePaymentAndDeliveryDateForProfile($mainProfile, $postValues);

                    $this->loggerStateProfile->info(sprintf(
                        'Main profile has been changed from edit payment page, ID:%s, post data: %s',
                        $mainProfile->getId(),
                        json_encode($postValues)
                    ));
                } catch (\Exception $e) {
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
                $newOrderDate = $this->helperProfile->calculatorNextOrderDateFromProfile($postValues['delivery_date_new'], $versionProfile->getProfileId());
                $this->profileRepository->save($versionProfile->setPaymentMethod($postValues['payment_method'])->setNextDeliveryDate($postValues['delivery_date_new'])->setNextOrderDate($newOrderDate)->getDataModel());
                $this->updateProductCart($versionProfile->getProfileId(), $postValues);
            }

            $tmpCriteria = $this->searchCriteriaBuilder
                ->addFilter('tmp_parent_profile_id', $profile->getProfileId())
                ->create();
            $tmpProfiles = $this->profileRepository
                ->getList($tmpCriteria)
                ->getItems();
            /** @var \Riki\Subscription\Api\Data\ApiProfileInterface $tmpProfile */
            foreach ($tmpProfiles as $tmpProfile) {
                $newDeliveryDate = $this->helperProfile->calculateDeliveryDateForTmp($profile->getProfileId())->format('Y-m-d');
                $newOrderDate = $this->helperProfile->calculatorNextOrderDateFromProfile($newDeliveryDate, $tmpProfile->getProfileId());

                if($this->checkDeleteProfileTemp($newDeliveryDate,$newOrderDate))
                {
                    //delete profile temp
                    $arrDataLog = [
                        'main_profile_id'  =>$profile->getProfileId(),
                        'data_profile_temp'=>$tmpProfile->getData(),
                        'new_delivery_date'=>$newDeliveryDate,
                        'new_order_date'   => $newOrderDate
                    ];
                    $this->deleteProfileTmpAndLog($tmpProfile,$arrDataLog);
                }else {
                    $this->profileRepository->save($tmpProfile->setPaymentMethod($postValues['payment_method'])->setNextDeliveryDate($newDeliveryDate)->setNextOrderDate($newOrderDate)->getDataModel());
                    $this->updateProductCart($tmpProfile->getProfileId(), ['delivery_date_new' => $newDeliveryDate, 'delivery_timeslot_new' => $postValues['delivery_timeslot_new']]);

                    $this->loggerStateProfile->info(sprintf(
                        'Tmp profile has been changed from edit payment page, ID:%s, new data: %s',
                        $tmpProfile->getId(),
                        json_encode([
                            'new_delivery_date'=>$newDeliveryDate,
                            'new_order_date'   => $newOrderDate
                        ])
                    ));
                }
            }

            $this->messageManager->addSuccess(__('Update profile successfully!'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->info($e);
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addError('An error occurred while saving payment method into profile');
        }

        return $result;
    }

    protected function updateProductCart($profileId, array $dataChange)
    {
        $profileModel = $this->profileFactory->create()->load($profileId);
        $profileProductCart = $profileModel->getProductCart();
        foreach ($profileProductCart as $productCart) {
            $productCart->setData('delivery_date', $dataChange['delivery_date_new']);
            $productCart->setData('delivery_time_slot', $dataChange['delivery_timeslot_new']);
            try {
                $productCart->save();
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    public function updateProfileBeforeAuthorize(\Riki\Subscription\Model\Profile\Profile $profile,$tradingId,$dataChange,$mainProfileId=null) {
        $nextDeliveryDateDefault = $dayOfWeek = $nthWeekdayOfMonth = null;
        $profile->setRandomTrading($tradingId);
        $nextDeliveryDate = $dataChange['delivery_date_new'];
        if ($profile->getType() == 'tmp') {
            $nextDeliveryDate = $this->helperProfile->calculateDeliveryDateForTmp($mainProfileId)->format('Y-m-d');

            $originProfile = $this->helperProfile->getProfileDataWithId($mainProfileId);
            $dayOfWeek = $originProfile['day_of_week'];
            $nthWeekdayOfMonth = $originProfile['nth_weekday_of_month'];
            $nextDeliveryDateDefault = $originProfile['data_generate_delivery_date'];
        } else {
            // NED-638: Calculation of the next delivery date
            // If subscription course setup with Next Delivery Date Calculation Option = "day of the week"
            // AND interval_unit="month"
            // AND not Stock Point
            $dayDeliveryDate = (int)date('d', strtotime($nextDeliveryDate));
            $nextDeliveryDateDefault = trim($nextDeliveryDate);

            if ($dayDeliveryDate > 28) {
                $nextDeliveryDate = $this->deliveryDateGenerateHelper->getLastDateOfMonth(
                    $nextDeliveryDate,
                    $nextDeliveryDateDefault
                );
            }

            if ($this->helperProfile->isDayOfWeekAndUnitMonthAndNotStockPoint($profile)) {
                if ($nextDeliveryDate == $profile->getData('next_delivery_date')
                    && $profile->getData('day_of_week') != null
                    && $profile->getData('nth_weekday_of_month') != null
                ) {
                    $dayOfWeek = $profile->getData('day_of_week');
                    $nthWeekdayOfMonth = $profile->getData('nth_weekday_of_month');
                } else {
                    $dayOfWeek = date('l', strtotime($nextDeliveryDate));
                    $nthWeekdayOfMonth = $this->deliveryDateGenerateHelper->calculateNthWeekdayOfMonth(
                        $nextDeliveryDate
                    );
                }
            }
        }

        $profile->setNextDeliveryDate($nextDeliveryDate);
        $nextOrderDate = $this->helperProfile->calculatorNextOrderDateFromProfile($nextDeliveryDate,$profile->getId());
        $profile->setNextOrderDate($nextOrderDate);

        // Update day_of_week and nth_weekday_of_month
        $profile->setData('day_of_week', $dayOfWeek);
        $profile->setData('nth_weekday_of_month', $nthWeekdayOfMonth);
        $profile->setData('data_generate_delivery_date', $nextDeliveryDateDefault);

        /*Update product cart*/
        /* @var $profileProductCart \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\Collection*/
        $profileProductCart = $profile->getProductCart(true);
        foreach ($profileProductCart as $productCart) {
            $productCart->setData('delivery_date',$nextDeliveryDate);
            $productCart->setData('delivery_time_slot',$dataChange['delivery_timeslot_new']);
            try{
                $productCart->save();
            }catch (\Exception $e) {
                throw $e;
            }
        }
        try {
            $profile->save();
            return true;
        } catch (\Exception $exception) {
            throw $exception;
        }

    }

    /**
     * Check delete profile temp
     *
     * @param $newDeliveryDate
     * @param $nextOrderDate
     * @return bool
     */
    public function checkDeleteProfileTemp($newDeliveryDate, $nextOrderDate)
    {
        $today = $this->timezone->date()->format('Y-m-d');

        $newDeliveryDate = $this->timezone->date($newDeliveryDate)->format('Y-m-d');
        $nextOrderDate = $this->timezone->date($nextOrderDate)->format('Y-m-d');

        if (($newDeliveryDate > $today) && ($nextOrderDate > $today)) {
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