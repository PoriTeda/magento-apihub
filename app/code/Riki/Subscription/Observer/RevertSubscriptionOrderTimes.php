<?php

namespace Riki\Subscription\Observer;

class RevertSubscriptionOrderTimes implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $subscriptionProfileHelper;
    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock
     */
    protected $outOfStockHelper;

    /**
     * RevertSubscriptionOrderTimes constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Subscription\Helper\Profile\Data $subscriptionProfileHelper
     * @param \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Helper\Profile\Data $subscriptionProfileHelper,
        \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
    ) {
        $this->logger = $logger;
        $this->profileFactory = $profileFactory;
        $this->subscriptionProfileHelper = $subscriptionProfileHelper;
        $this->outOfStockHelper = $outOfStockHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        /*cancel incomplete order RIM-7536*/
        if ($order->getData('is_incomplete_generate_profile_order')) {
            return;
        }

        /*Simulate doesn't need to update Sale Rule */
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }

        $subscriptionProfileId = $order->getSubscriptionProfileId();

        if (empty($subscriptionProfileId)) {
            return;
        }

        if ($this->outOfStockHelper->isOutOfStockOrder($order->getId())) {
            return;
        }

        $profileModel = $this->getProfileById($subscriptionProfileId);

        if ($profileModel) {

            /*revert order time for main profile*/
            $this->revertOrderTimesByProfileData($profileModel);

            $versionId = $this->subscriptionProfileHelper->checkProfileHaveVersion($subscriptionProfileId);

            if (!empty($versionId)) {
                $versionProfileModel = $this->getProfileById($versionId);

                if ($versionProfileModel) {
                    /*revert order time for version profile*/
                    $this->revertOrderTimesByProfileData($versionProfileModel);
                }
            }

            $tmp = $this->subscriptionProfileHelper->getTmpProfile($subscriptionProfileId);

            if ($tmp) {
                $tmpId = $tmp->getData('linked_profile_id');
                $tmpProfileModel = $this->getProfileById($tmpId);

                if (!empty($tmpProfileModel)) {
                    /*revert order time for tmp profile*/
                    $this->revertOrderTimesByProfileData($tmpProfileModel);
                }
            }
        }
    }

    /**
     * Get profile by Id
     *
     * @param $profileId
     * @return bool|\Riki\Subscription\Model\Profile\Profile
     */
    public function getProfileById($profileId)
    {
        /** @var \Riki\Subscription\Model\Profile\Profile $profileModel */
        $profileModel = $this->profileFactory->create();
        $profileModel->load($profileId);

        if ($profileModel->getId()) {
            return $profileModel;
        }
        return false;
    }

    /**
     * Revert order times
     *
     * @param $profileModel
     */
    public function revertOrderTimesByProfileData($profileModel)
    {
        $orderTimes = $profileModel->getData('order_times');

        if ($orderTimes > 1) {
            $profileModel->setData('order_times', $orderTimes - 1);

            try {
                $profileModel->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }
}