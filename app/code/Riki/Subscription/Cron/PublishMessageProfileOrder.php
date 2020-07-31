<?php

namespace Riki\Subscription\Cron;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Riki\Subscription\Model\Profile\Profile as ModelProfile;
use Magento\Framework\MessageQueue\Consumer\ConfigInterface as ConsumerConfigInterface;
use \Riki\Sales\Model\ResourceModel\Order\OrderStatus;

class PublishMessageProfileOrder
{
    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_helperProfileData;
    /**
     * @var \Riki\Subscription\Logger\LoggerPublishMessageQueue
     */
    protected $_logger;

    /**
     * @var \Riki\Subscription\Logger\HandlerOrder
     */
    protected $handlerCSV;

    /**
     * @var \Riki\Subscription\Logger\LoggerOrder
     */
    protected $_loggerOrder;

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
     * @var \Riki\Subscription\Api\GenerateOrder\ProfileBuilderInterface
     */
    protected $profileBuilder;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;
    /**
     * @var \Riki\Subscription\Model\Profile\Order\ProfileOrderFactory
     */
    protected $profileOrderFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZone;
    /* @var \Riki\Subscription\Model\Profile\ResourceModel\ProfileLink\CollectionFactory */
    protected $profileLinkCollection;
    /**
     * @var \Magento\Framework\MessageQueue\ConfigInterface
     */
    private $queueConfig;
    /**
     * @var ConsumerConfigInterface
     */
    private $consumerConfig;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * PublishMessageProfileOrder constructor.
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfileData
     * @param \Riki\Subscription\Logger\LoggerPublishMessageQueue $logger
     * @param \Riki\Subscription\Logger\HandlerOrder $handlerCSV
     * @param \Riki\Subscription\Logger\LoggerOrder $loggerOrder
     * @param \Riki\Subscription\Helper\Order\Data $orderData
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Subscription\Model\Version\VersionFactory $versionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Riki\Subscription\Api\GenerateOrder\ProfileBuilderInterface $profileBuilderInterface
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Riki\Subscription\Model\Profile\Order\ProfileOrderFactory $profileOrderFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Riki\Subscription\Model\Profile\ResourceModel\ProfileLink\CollectionFactory $profileLinkCollection
     * @param \Magento\Framework\MessageQueue\ConfigInterface $queueConfig
     * @param ConsumerConfigInterface $consumerConfig
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        \Riki\Subscription\Helper\Profile\Data $helperProfileData,
        \Riki\Subscription\Logger\LoggerPublishMessageQueue $logger,
        \Riki\Subscription\Logger\HandlerOrder $handlerCSV,
        \Riki\Subscription\Logger\LoggerOrder $loggerOrder,
        \Riki\Subscription\Helper\Order\Data $orderData,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Model\Version\VersionFactory $versionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Riki\Subscription\Api\GenerateOrder\ProfileBuilderInterface $profileBuilderInterface,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Riki\Subscription\Model\Profile\Order\ProfileOrderFactory $profileOrderFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\Subscription\Model\Profile\ResourceModel\ProfileLink\CollectionFactory $profileLinkCollection,
        \Magento\Framework\MessageQueue\ConfigInterface $queueConfig,
        ConsumerConfigInterface $consumerConfig,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->_helperProfileData = $helperProfileData;
        $this->_logger = $logger;
        $this->handlerCSV = $handlerCSV;
        $this->_loggerOrder = $loggerOrder;
        $this->_orderData = $orderData;
        $this->_profileFactory = $profileFactory;
        $this->_profileVersion = $versionFactory;
        $this->_datetime = $datetime;
        $this->profileBuilder = $profileBuilderInterface;
        $this->publisher = $publisher;
        $this->profileOrderFactory = $profileOrderFactory;
        $this->timeZone = $timezone;
        $this->profileLinkCollection = $profileLinkCollection;
        $this->queueConfig = $queueConfig;
        $this->consumerConfig = $consumerConfig;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }


    public function execute()
    {
        $aProfilePublish = [];
        $aProfileHaveVersion = [];
        $aProfileNormal = [];

        // Get list profile id has order status is 'PENDING_FOR_MACHINE'
        $orderCollection = $this->orderCollectionFactory->create()
            ->addFieldToSelect('subscription_profile_id')
            ->addAttributeToFilter('status', OrderStatus::STATUS_ORDER_PENDING_FOR_MACHINE);

        $originDate = $this->timeZone->formatDateTime($this->_datetime->gmtDate(), 2);
        $today = $this->_datetime->gmtDate('Y-m-d', $originDate);
        $profileCollection = $this->_profileFactory->create()->getCollection();
        $profileCollection->addFieldToSelect('profile_id')
            ->getSelect()
            ->distinct(true)
            ->joinLeft(
                ['v' => 'subscription_profile_version'],
                'main_table.profile_id = v.rollback_id and v.`status` = 1',
                []
            )->joinLeft(
                ['pv' => 'subscription_profile'],
                'v.moved_to = pv.profile_id ',
                []
            );
        $orWhereConditions = [
            $profileCollection->getConnection()->quoteInto(
                '(pv.profile_id is null AND main_table.next_order_date <= ? AND main_table.`type` is null 
                AND main_table.`status` = 1 AND main_table.publish_message !=1)',
                $today
            ),
            $profileCollection->getConnection()->quoteInto(
                '(pv.profile_id is not null AND pv.next_order_date <= ? 
                AND pv.`status` = 1 AND pv.publish_message != 1)',
                $today
            )
        ];

        // Add condition for case profile has order status is 'PENDING_FOR_MACHINE'
        $andWhereCondition = $profileCollection->getConnection()->quoteInto(
            'main_table.profile_id NOT IN (?)',
            $orderCollection->getSelect()
        );

        // Add condition for case profile monthly fee
        $orMonthlyFeeConfirmedConditions = [
            $profileCollection->getConnection()->quoteInto(
                '(pv.profile_id is null AND main_table.next_order_date <= ? AND main_table.`type` is null 
                AND main_table.`status` = 1 AND main_table.publish_message !=1 
                AND main_table.is_monthly_fee_confirmed = 1)',
                $today
            ),
            $profileCollection->getConnection()->quoteInto(
                '(pv.profile_id is not null AND pv.next_order_date <= ? 
                AND pv.`status` = 1 AND pv.publish_message != 1 AND pv.is_monthly_fee_confirmed = 1)',
                $today
            )
        ];
        $orWhereCondition = implode(' OR ', $orWhereConditions);
        $orMonthlyFeeConfirmedCondition = implode(' OR ', $orMonthlyFeeConfirmedConditions);
        $profileCollection->getSelect()->where(
            '((' . $orWhereCondition . ')' . ' AND ' . $andWhereCondition . ')' .
            ' OR ' . '(' . $orMonthlyFeeConfirmedCondition . ')'
        );

        $profileTmpCollection = $this->profileLinkCollection->create()
            ->addFieldToFilter('profile_id', ['in' => $profileCollection->getSelectSql()]);
        $profileHaveLink = [];
        foreach ($profileTmpCollection as $profileTmp) {
            $profileHaveLink[] = $profileTmp->getData('profile_id');
        }

        foreach ($profileCollection as $profile) {
            if (!(in_array($profile->getId(), $profileHaveLink) && $profile->getData('payment_method') == null)
                && !empty($profile->getData('frequency_unit')) && !empty($profile->getData('frequency_interval'))) {
                $aProfilePublish[] = $profile->getId();
            }

            if (empty($profile->getData('frequency_unit')) || empty($profile->getData('frequency_interval'))) {
                $this->handlerCSV->setDynamicFileLog('aGenerateOrderSubscription');
                $this->_loggerOrder->setHandlers(['system' => $this->handlerCSV]);
                $this->_loggerOrder->info('Please check frequency_unit or frequency_interval for profile #' . $profile->getId());
            }
        }

        /*try to sort profile */
        $aProfileQueuePublish = $this->sortProfile($aProfilePublish);

        foreach ($aProfileQueuePublish as $queueName => $aListProfileId) {

            foreach ($aListProfileId as $profileId) {
                $profileCreateOrder = $this->profileOrderFactory->create();
                $profileCreateOrder->setProfileId($profileId);
                $profileItemBuilder = $this->profileBuilder->setItems([$profileCreateOrder]);
                try {
                    \Magento\Framework\App\ObjectManager::getInstance()->get("Nestle\Debugging\Helper\DebuggingHelper")
                        ->inClass($this)
                        ->logServerIp()
                        ->log("publish to queue: " . $queueName)
                        ->logBacktrace()
                        ->save("order_subscription_generation");
                    $this->publisher->publish($queueName, $profileItemBuilder);
                    $this->updateProfile($profileId);
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }

        }

        return $this;
    }

    /**
     * @param $aProfilePublish
     */
    public function sortProfile($aProfilePublish)
    {

        $aProfileQueuePublish = [];

        /*init queue name*/
        $rangeCreateQueue = range("a", "z");
        $basicQueueName = '%s.profile.generate.order';
        $maxConsumer = $this->getNumberOfQueueIsRunning();
        $i = 0;
        foreach ($rangeCreateQueue as $queueName) {

            if ($i >= $maxConsumer) {
                break;
            }
            $aProfileQueuePublish[sprintf($basicQueueName, $queueName)] = [];
            $i++;
        }

        if (!empty($aProfilePublish)) {
            $profileModel = $this->_profileFactory->create()->getCollection();
            $profileModel->addFieldToSelect('profile_id');
            $profileModel->addFieldToSelect('customer_id');
            $profileModel->addFieldToFilter('profile_id', array('in' => $aProfilePublish));
            $aProfileSorted = [];
            foreach ($profileModel as $profileItem) {
                if ($profileItem->getData('profile_id') && $profileItem->getData('customer_id')) {
                    $aProfileSorted[$profileItem->getData('customer_id')][] = $profileItem->getData('profile_id');
                }
            }
            $aProfileSortedCount = [];

            foreach ($aProfileSorted as $iCustomerId => $aProfileCustomer) {
                $aProfileSortedCount[$iCustomerId] = count($aProfileCustomer);
            }

            arsort($aProfileSortedCount);

            $aProfileQueuePublishCount = $aProfileQueuePublish;

            foreach ($aProfileQueuePublish as $queueName => $aListProfile) {
                $aProfileQueuePublishCount[$queueName] = count($aListProfile);
            }

            foreach ($aProfileSortedCount as $iCustomerId => $iCountProfile) {

                /*queue got at lease profile will take more profile*/
                asort($aProfileQueuePublishCount);
                /*get first queue*/
                reset($aProfileQueuePublishCount);
                $queueName = key($aProfileQueuePublishCount);

                /*push count profile for easy sort priorities of queue*/
                $aProfileQueuePublishCount[$queueName] += $iCountProfile;
                /*push list profile into queue*/
                $aProfileCustomer = $aProfileSorted[$iCustomerId];

                $aProfileQueuePublish[$queueName] = array_merge($aProfileQueuePublish[$queueName], $aProfileCustomer);
            }

            /*try to sort product base on queue*/


            return $aProfileQueuePublish;
        }

        return $aProfilePublish;
    }

    protected function getNumberOfQueueIsRunning()
    {
        $numOfQueue = 0;
        $rangeCreateQueue = range("a", "z");
        $basicQueueName = '%sGenerateOrderSubscription';

        foreach ($rangeCreateQueue as $queueName) {
            try {
                if ($this->consumerConfig->getConsumer(sprintf($basicQueueName, $queueName))) {
                    $numOfQueue++;
                }
            } catch (\Exception $e) {
                \Magento\Framework\App\ObjectManager::getInstance()->get("Nestle\Debugging\Helper\DebuggingHelper")
                    ->inClass($this)
                    ->logServerIp()
                    ->log("error: " . $e->getMessage())
                    ->logBacktrace()
                    ->save("order_subscription_generation");
            }
        }


        \Magento\Framework\App\ObjectManager::getInstance()->get("Nestle\Debugging\Helper\DebuggingHelper")
            ->inClass($this)
            ->logServerIp()
            ->log("numOfQueue: " . $numOfQueue)
            ->logBacktrace()
            ->save("order_subscription_generation");

        return $numOfQueue;
    }


    public function updateProfile($profileId)
    {
        try {
            if ($movedToId = $this->_helperProfileData->checkProfileHaveVersion($profileId)) {
                $profileModel = $this->_profileFactory->create()->load($movedToId);
            } else {
                $profileModel = $this->_profileFactory->create()->load($profileId);
            }

            if ($profileModel->getId()) {
                $profileModel->setData('publish_message', 1);
                $profileModel->save();
            }
            $this->_logger->info("Profile #" . $profileId . " published to message queue");
        } catch (Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }


}