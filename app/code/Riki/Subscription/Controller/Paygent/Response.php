<?php

namespace Riki\Subscription\Controller\Paygent;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\Result\RawFactory;

class Response extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Riki\Subscription\Logger\Logger
     */
    protected $_subscriptionLogger;
    /**
     * @var \Riki\Subscription\Model\Paygent
     */
    protected $modelPaygent;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $_profileFactory;
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $_rawFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Bluecom\Paygent\Model\HistoryUsed
     */
    protected $historyUsed;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    /**
     * Response constructor.
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Subscription\Logger\Logger $subscriptionLogger
     * @param \Riki\Subscription\Model\Paygent $modelPaygent
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param RawFactory $rawFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Bluecom\Paygent\Model\HistoryUsed $historyUsed
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Subscription\Logger\Logger $subscriptionLogger,
        \Riki\Subscription\Model\Paygent $modelPaygent,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Framework\Controller\Result\RawFactory $rawFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Bluecom\Paygent\Model\HistoryUsed $historyUsed,
        \Riki\Subscription\Helper\Profile\Data $helperProfile
    ) {
        parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
        $this->_subscriptionLogger = $subscriptionLogger;
        $this->modelPaygent = $modelPaygent;
        $this->_profileFactory = $profileFactory;
        $this->_rawFactory = $rawFactory;
        $this->timezone = $timezone;
        $this->dateTime = $dateTime;
        $this->historyUsed = $historyUsed;
        $this->profileRepository = $profileRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helperProfile = $helperProfile;
    }

    protected $_request;
    protected $_response;

    public function execute()
    {
        $this->_request = $this->getRequest();
        $this->_response = $this->getResponse();
        $rawResult = $this->_rawFactory->create();

        //write log to file when enable debug flag
        if ($this->_scopeConfig->getValue('payment/paygent/debug')) {
            //write log to file when enable debug flag
            $this->_subscriptionLogger->info(json_encode($this->_request->getParams()));
        }

        try {
            $result = [];
            $result['payment_id'] = $this->_request->getParam('seq_payment_id');
            $result['trading_id'] = $this->_request->getParam('trading_id');
            $result['payment_type'] = $this->_request->getParam('payment_type');
            $result['payment_amount'] = $this->_request->getParam('amount');

            /*get Profile Id from random trading*/
            $randomTrading = $this->_request->getParam('trading_id');
            $profileCollection = $this->_profileFactory->create()->getCollection();
            $profileCollection->addFieldToFilter('random_trading', $randomTrading);
            $profileCollection->setFlag('original', 1);
            $profileCollection->setPageSize(1);
            $profileCollection->addFieldToSelect('profile_id');
            if (sizeof($profileCollection) >= 1) {
                $profileId = $profileCollection->getFirstItem()->getData('profile_id');
            } else {
                return $rawResult->setContents('result=0');
            }
            $profileModel = $this->_profileFactory->create()->load($profileId);
            if ($profileModel->getId()) {
                //save trading for main profile
                $profileModel->setTradingId($randomTrading);
                $profileModel->setPaymentMethod('paygent');
                $profileModel->setRandomTrading('');
                try {
                    $profileModel->save();

                    //save trading for version profile
                    $versionId = $this->helperProfile->checkProfileHaveVersion($profileModel->getId());
                    if ($versionId) {
                        $versionProfile = $this->_profileFactory->create()->load($versionId);
                        if ($versionProfile->getId()) {
                            $versionProfile->setTradingId($randomTrading);
                            $versionProfile->setData('payment_method', 'paygent');
                            $versionProfile->setRandomTrading('');
                            try {
                                $versionProfile->save();
                            } catch (\Exception $e) {
                                $this->_subscriptionLogger->critical($e);
                            }
                        }
                    }

                    /**
                     * NED - 48
                     * Update trading id for main profile
                     * if profile main has payment method = paygent, trading_id = null
                     */
                    if ($profileModel->getType() == 'tmp') {
                        $mainProfile = $this->helperProfile->getProfileMainByProfileTmpId($profileModel->getId());
                        if ($mainProfile) {
                            if (!$mainProfile->getTradingId() && $mainProfile->getPaymentMethod() == 'paygent') {
                                $mainProfile->setTradingId($randomTrading);
                                $mainProfile->save();
                            }
                        }
                    }

                    //save trading for profile temp
                    $tempProfileLink = $this->helperProfile->getTmpProfile($profileModel->getId());
                    if ($tempProfileLink) {
                        $tempProfileId = $tempProfileLink->getLinkedProfileId();
                        $tempProfile = $this->_profileFactory->create()->load($tempProfileId);
                        if ($tempProfile->getId()) {
                            $tempProfile->setTradingId($randomTrading);
                            $tempProfile->setData('payment_method', 'paygent');
                            $tempProfile->setRandomTrading('');
                            try {
                                $tempProfile->save();
                            } catch (\Exception $e) {
                                $this->_subscriptionLogger->critical($e);
                            }
                        }
                    }

                    // case null payment method
                    if (!$profileModel->getOrigData('payment_method')
                        && $this->_request->getParam('payment_method_update')
                    ) {
                        $versionCriteria = $this->searchCriteriaBuilder
                            ->addFilter('version_parent_profile_id', $profileModel->getProfileId())
                            ->addFilter('subscription_profile_version.status', 1)
                            ->create();
                        $versionProfiles = $this->profileRepository
                            ->getList($versionCriteria)
                            ->getItems();
                        /** @var \Riki\Subscription\Api\Data\ApiProfileInterface $versionProfile */
                        foreach ($versionProfiles as $versionProfile) {
                            $versionProfile->setTradingId($profileModel->getTradingId());
                            $versionProfile->setPaymentMethod($profileModel->getPaymentMethod());
                            $this->profileRepository->save($versionProfile->getDataModel());
                        }

                        $tmpCriteria = $this->searchCriteriaBuilder
                            ->addFilter('tmp_parent_profile_id', $profileModel->getProfileId())
                            ->create();
                        $tmpProfiles =  $this->profileRepository
                            ->getList($tmpCriteria)
                            ->getItems();
                        /** @var \Riki\Subscription\Api\Data\ApiProfileInterface $tmpProfile */
                        foreach ($tmpProfiles as $tmpProfile) {
                            $tmpProfile->setTradingId($profileModel->getTradingId());
                            $tmpProfile->setPaymentMethod($profileModel->getPaymentMethod());
                            $this->profileRepository->save($tmpProfile->getDataModel());
                        }
                    }
                } catch (\Exception $e) {
                    $this->_subscriptionLogger->critical($e);
                    $rawResult->setContents(__('Cannot save information of this profile, please try again!'));
                    return $rawResult->setHttpResponseCode(400);
                }

                //Save paygent history used
                $paygentHistory = [
                    'customer_id' => $profileModel->getCustomerId(),
                    'order_number' => '',
                    'profile_id' => $profileModel->getId(),
                    'trading_id' => $randomTrading,
                    'used_date' => $this->timezone->formatDateTime($this->dateTime->gmtDate(), 2),
                    'type' => 'profile_update'
                ];
                //save history used
                $this->historyUsed->savePaygentHistory($paygentHistory);
            }

            //verify hash string
            //$this->_calcHash($result, $hash);

            //cancel authorize
            $this->modelPaygent->void($result);
        } catch (\Exception $e) {
            $this->_subscriptionLogger->critical($e);
            $rawResult->setContents($e->getMessage());
            return $rawResult->setHttpResponseCode(400);
        }

        return $rawResult->setContents('result=0');
    }

    /**
     * @param $result
     * @param $hash
     * @return bool
     * @throws LocalizedException
     */
    protected function _calcHash($result, $hash)
    {
        $str = $result['payment_type'] .
            $result['payment_amount'] .
            $this->_scopeConfig->getValue('payment/paygent/hash_key') .
            $result['trading_id'] .
            $result['payment_id'];

        $hash_str = hash("sha256", $str);
        if (substr($hash_str, 0, 63) != substr($hash, 0, 63)) {
            throw new LocalizedException(__('Request is modified by someone.'));
        }

        return true;
    }
}
