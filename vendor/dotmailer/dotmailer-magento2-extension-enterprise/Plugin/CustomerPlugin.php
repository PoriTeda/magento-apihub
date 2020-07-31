<?php

namespace Dotdigitalgroup\Enterprise\Plugin;

/**
 * Class CustomerPlugin
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class CustomerPlugin
{
    /**
     * @var object
     */
    private $reward;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\Reward\Model\ResourceModel\Reward\History\CollectionFactory
     */
    private $rewardHistoryCollectionFactory;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Customer
     */
    private $customerSegmentCustomerResource;

    /**
     * @var \Magento\Reward\Helper\Data
     */
    private $rewardHelper;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    private $customer;

    /**
     * @var \Dotdigitalgroup\Enterprise\Helper\Data
     */
    private $helper;

    /**
     * CustomerPlugin constructor.
     *
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Reward\Model\ResourceModel\Reward\History\CollectionFactory $rewardHistoryCollectionFactory
     * @param \Magento\CustomerSegment\Model\ResourceModel\Customer $customerSegmentCustomerResource
     * @param \Magento\Reward\Helper\Data $rewardHelper
     * @param \Dotdigitalgroup\Enterprise\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Reward\Model\ResourceModel\Reward\History\CollectionFactory $rewardHistoryCollectionFactory,
        \Magento\CustomerSegment\Model\ResourceModel\Customer $customerSegmentCustomerResource,
        \Magento\Reward\Helper\Data $rewardHelper,
        \Dotdigitalgroup\Enterprise\Helper\Data $helper
    ) {
        $this->dateTime = $dateTime;
        $this->rewardHistoryCollectionFactory = $rewardHistoryCollectionFactory;
        $this->customerSegmentCustomerResource = $customerSegmentCustomerResource;
        $this->rewardHelper = $rewardHelper;
        $this->helper = $helper;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Customer $subject
     * @param \Magento\Customer\Model\Customer $customer
     * @return mixed
     */
    public function beforeSetCustomerData(\Dotdigitalgroup\Email\Model\Apiconnector\Customer $subject, $customer)
    {
        $this->customer = $customer;
        $websiteId = $customer->getWebsiteId();
        $this->reward = false;

        if ($this->helper->getRewardPointMapping($websiteId)) {
            $customer->setRewardPoints($this->getRewardPoints());
        }
        if ($this->helper->getRewardAmountMapping($websiteId)) {
            $customer->setRewardAmmount($this->getRewardAmmount());
        }
        if ($this->helper->getExpirationDateMapping($websiteId)) {
            $customer->setExpirationDate($this->getExpirationDate());
        }
        if ($this->helper->getLastUsedDateMapping($websiteId)) {
            $customer->setLastUsedDate($this->getLastUsedDate());
        }
        if ($this->helper->getCustomerSegmentMapping($websiteId)) {
            $customer->setCustomerSegments($this->getCustomerSegments());
        }

        return [$customer];
    }

    /**
     * Reward points balance.
     *
     * @return int
     */
    public function getRewardPoints()
    {
        if (! $this->reward) {
            $this->setReward();
        }

        if ($this->reward !== true) {
            return $this->reward->getPointsBalance();
        }

        return '';
    }

    /**
     * Currency amount points.
     *
     * @return mixed
     */
    public function getRewardAmmount()
    {
        if (!$this->reward) {
            $this->setReward();
        }

        if ($this->reward !== true) {
            return $this->reward->getCurrencyAmount();
        }

        return '';
    }

    /**
     * Expiration date to use the points.
     *
     * @return string
     */
    public function getExpirationDate()
    {
        //set reward for later use
        if (!$this->reward) {
            $this->setReward();
        }

        if ($this->reward !== true) {
            $expiredAt = $this->reward->getExpirationDate();

            if ($expiredAt) {
                $date = $this->dateTime->formatDate($expiredAt, true);
            } else {
                $date = '';
            }

            return $date;
        }

        return '';
    }

    /**
     * Get the customer reward.
     *
     * @return void
     */
    public function setReward()
    {
        $rewardData = $this->rewardHelper;
        $historyCollectionFactory = $this->rewardHistoryCollectionFactory;
        $collection = $historyCollectionFactory->create()
            ->addCustomerFilter($this->customer->getId())
            ->addWebsiteFilter($this->customer->getWebsiteId())
            ->setExpiryConfig($rewardData->getExpiryConfig())
            ->addExpirationDate($this->customer->getWebsiteId())
            ->skipExpiredDuplicates()
            ->setDefaultOrder();

        $item = $collection->setPageSize(1)
            ->setCurPage(1)
            ->getFirstItem();

        $this->reward = $item;
    }

    /**
     * Customer segments id.
     *
     * @return string
     */
    public function getCustomerSegments()
    {
        $customerSegmentResource = $this->customerSegmentCustomerResource;
        $segmentIds = $customerSegmentResource->getCustomerWebsiteSegments(
            $this->customer->getId(),
            $this->customer->getWebsiteId()
        );

        if (! empty($segmentIds)) {
            return implode(',', $segmentIds);
        }

        return '';
    }

    /**
     * Last used reward points.
     *
     * @return string
     */
    public function getLastUsedDate()
    {
        $historyCollectionFactory = $this->rewardHistoryCollectionFactory;
        //last used from the reward history based on the points delta used enterprise module
        $lastUsed = $historyCollectionFactory->create()
            ->addCustomerFilter($this->customer->getId())
            ->addWebsiteFilter($this->customer->getWebsiteId())
            ->addFieldToFilter('points_delta', ['lt' => 0])
            ->setDefaultOrder()
            ->setPageSize(1)
            ->getFirstItem()
            ->getCreatedAt();
        //for any valid date
        if ($lastUsed) {
            return $this->dateTime->formatDate($lastUsed, true);
        }

        return '';
    }
}
