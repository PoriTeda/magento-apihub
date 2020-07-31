<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Plugin;

class CustomerRegister
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Reward factory
     *
     * @var \Magento\Reward\Model\RewardFactory
     */
    protected $_rewardFactory;

    /**
     * Core model store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Reward helper
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData;

    /**
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reward\Model\RewardFactory $rewardFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reward\Model\RewardFactory $rewardFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_rewardData = $rewardData;
        $this->_storeManager = $storeManager;
        $this->_rewardFactory = $rewardFactory;
        $this->_logger = $logger;
    }

    /**
     * Update reward points after customer register
     *
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param \Closure $proceed
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $hash
     * @param string $redirectUrl
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCreateAccountWithPasswordHash(
        \Magento\Customer\Model\AccountManagement $subject,
        \Closure $proceed,
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        $hash,
        $redirectUrl = ''
    ) {
        if (!$this->_rewardData->isEnabledOnFront()) {
            return $proceed($customer, $hash, $redirectUrl);
        }
        $subscribeByDefault = $this->_rewardData->getNotificationConfig(
            'subscribe_by_default',
            $this->_storeManager->getStore()->getWebsiteId()
        );
        $customer->setCustomAttribute('reward_update_notification', $subscribeByDefault);
        $customer->setCustomAttribute('reward_warning_notification', $subscribeByDefault);

        $customer = $proceed($customer, $hash, $redirectUrl);

        try {
            $this->_rewardFactory->create()->setCustomer(
                $customer
            )->setActionEntity(
                $customer
            )->setStore(
                $this->_storeManager->getStore()->getId()
            )->setAction(
                \Magento\Reward\Model\Reward::REWARD_ACTION_REGISTER
            )->updateRewardPoints();
        } catch (\Exception $e) {
            //save exception if something went wrong during saving reward and allow to register customer
            $this->_logger->critical($e);
        }

        return $customer;
    }
}
