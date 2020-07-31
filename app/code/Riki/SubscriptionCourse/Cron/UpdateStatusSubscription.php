<?php
namespace Riki\SubscriptionCourse\Cron;

class UpdateStatusSubscription
{
    /**
     * Date Time Class
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;

    /**
     *  Time Zone Class
     *
     * @var  \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $_stdTimezone;

    /**
     * Subscription Course Model Resource Collection
     *
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course\Collection
     */
    protected $_subscriptionCourseModelCollectionFactory;

    /**
     * Logger
     *
     * @var  \Riki\SubscriptionCourse\Logger\LoggerUpdateSubStatus
     */
    protected $_loggerUpdateSubStatus;

    /**
     *  Subscription Helper
     *
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $_subHelper;

    /**
     * Construct
     *
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course\Collection $subsCollectionFactory SubCollection
     * @param \Magento\Framework\Stdlib\DateTime\Timezone                           $timezone              Timezone
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                           $dateTime              Datetime
     */

    public function __construct(
        \Riki\SubscriptionCourse\Model\ResourceModel\Course\CollectionFactory $subsCollection,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\SubscriptionCourse\Logger\LoggerUpdateSubStatus $loggerUpdateSubStatus,
        \Riki\SubscriptionCourse\Helper\Data $subHelper
    ){
        $this->_subHelper = $subHelper;
        $this->_dateTime = $dateTime;
        $this->_stdTimezone = $timezone;
        $this->_subscriptionCourseModelCollectionFactory = $subsCollection;
        $this->_loggerUpdateSubStatus = $loggerUpdateSubStatus;
    }

    public function execute()
    {
        if ($this->_subHelper->getStoreConfig(\Riki\SubscriptionCourse\Helper\Data::CONFIG_SUB_UPDATE_STATUS) == 0) {
            return;
        }
        $currentDate = $this->_stdTimezone->date()->setTime(0,0,0)->format('Y-m-d H:s:i');
        $subscriptionCollection = $this->_subscriptionCourseModelCollectionFactory->create()->addFieldToSelect(array('launch_date', 'close_date','is_enable'));

        $this->_loggerUpdateSubStatus->info('------------------------------');
        $this->_loggerUpdateSubStatus->info(__('Subscription Update Status Cron Job Running At: '). $currentDate);
        $this->_loggerUpdateSubStatus->info('------------------------------');

        if ($subscriptionCollection->getSize() > 0) {
            foreach($subscriptionCollection as $subscriptionItem) {
                $launchDate = $subscriptionItem->getData('launch_date');
                $closeDate = $subscriptionItem->getData('close_date');
                $trueSubStatus = $this->trueSubStatus($currentDate, $launchDate, $closeDate);
                if ($subscriptionItem->getData('is_enable') != $trueSubStatus) {
                    $subscriptionItem->setData('is_enable', $trueSubStatus);
                    try {
                        $subscriptionItem->setData('is_update_status',1);
                        $subscriptionItem->save();
                        $this->_loggerUpdateSubStatus->info('Update status for subscription: '.$subscriptionItem->getId(). ' to '.$trueSubStatus);
                    } catch (\Exception $e) {
                        $this->_loggerUpdateSubStatus->info('Can not update status for subscription: '.$subscriptionItem->getId());
                        $this->_loggerUpdateSubStatus->info($e->getMessage());
                    }
                }
            }
        }

        $this->_loggerUpdateSubStatus->info('-------------------End Cron------------------');
    }

    public function trueSubStatus($currentTime, $launchDate, $closeDate)
    {
        if ($launchDate) {
            $intLaunchDate = strtotime($launchDate);
        } else {
            $intLaunchDate = 0;
        }

        if ($closeDate) {
            $intCloseDate = strtotime($closeDate);
        } else {
            $intCloseDate = 0;
        }

        if ($currentTime) {
            $intCurrentDate = strtotime($currentTime);
        } else {
            $intCurrentDate = 0;
        }

        if ($intLaunchDate <= $intCurrentDate) {
            if ($intCloseDate == 0) {
                return 1;
            } else {
                if ($intCurrentDate <= $intCloseDate) {
                    return 1;
                } else {
                    return 0;
                }
            }
        } else {
            return 0;
        }
    }
}